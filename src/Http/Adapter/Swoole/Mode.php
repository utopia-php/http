<?php

namespace Utopia\Http\Adapter\Swoole;

use Swoole\Constant;

/**
 * Opinionated Swoole HTTP server configuration.
 *
 * Each option is annotated with Swoole's actual default and why we
 * deviate (or why we restate it explicitly). Citations refer to paths
 * inside the swoole-src repository.
 */
enum Mode {
    /**
     * HYPERLOOP_A - Process-per-request mode.
     *
     * Coroutines disabled, with 6x CPU worker count and preemptive
     * dispatch (mode 3) so requests are routed to idle workers rather
     * than queued behind busy ones. Suited to blocking, CPU-bound, or
     * legacy synchronous workloads where a coroutine scheduler would
     * add overhead without benefit.
     */
    case HYPERLOOP_A;

    /**
     * HYPERLOOP_B - Coroutine-enabled mode.
     *
     * Coroutines enabled, with worker count matched to CPU cores and
     * FD-bound dispatch (mode 2) so each connection is pinned to a
     * stable worker. This plays well with per-worker connection pools
     * and is suited to I/O-bound workloads that benefit from
     * cooperative scheduling. SEND_YIELD allows large responses to
     * yield instead of blocking the worker.
     *
     * In loving memory of Binyamin Yawitz (1991-2025), aka byawitz
     * on GitHub (https://github.com/byawitz), who first brought
     * coroutines to utopia-php. His prototype on this adapter
     * (commits f4ce8a4, 1397674, 6d5c75a, "feat: adding Coroutine server")
     * planted the seed that grew into this mode. He never got to see it
     * finished; we did our best to honor the path he started.
     *
     * Thank you, Binyamin.
     */
    case HYPERLOOP_B;

    /**
     * Settings shared across modes. Per-mode arrays merge on top.
     *
     * Server::set() forwards this array to ports[0]::set() too, so
     * port-level options (tcp_*, open_tcp_*) work here for the primary
     * port without a separate Port::set() call.
     */
    public static function defaults(): array
    {
        return [
            // Default: false (swoole_server.h:831). Restated explicitly
            // because utopia-php/http handles compression in user space;
            // letting Swoole compress on top would double-encode.
            Constant::OPTION_HTTP_COMPRESSION => false,

            // Struct default: false (swoole_server.h:248). Parser default
            // when the key is absent: true (swoole_server_port.cc:308-312).
            // We restate to lock the value across that disagreement. For
            // HTTP, disabling Nagle is unambiguously correct.
            Constant::OPTION_OPEN_TCP_NODELAY => true,

            // Default: 0 / off (swoole_server.h:188). TFO saves an RTT on
            // connection setup when both kernel and client support it; on
            // unsupported paths it transparently falls back. No downside.
            Constant::OPTION_TCP_FASTOPEN => true,

            // Default: 0 / off (swoole_server.h:184). Delays accept()
            // return until the first request bytes arrive; saves a worker
            // wake-up on every junk/scan connection. The integer is the
            // max-wait seconds before accepting anyway.
            Constant::OPTION_TCP_DEFER_ACCEPT => 1,

            // Default: false (swoole_server.h:855). SO_REUSEPORT lets the
            // kernel load-balance accept() across reactors instead of all
            // contending on one accept queue. Linux win; no-op elsewhere.
            Constant::OPTION_ENABLE_REUSE_PORT => true,

            // Default: 3 seconds (SW_WORKER_MAX_WAIT_TIME). Deadline a
            // worker gets to drain in-flight requests on graceful
            // shutdown/reload before being force-killed. 3s is too short
            // for typical HTTP work; 30 stays under the K8s default
            // terminationGracePeriodSeconds.
            Constant::OPTION_MAX_WAIT_TIME => 30,

            // Default: unlimited (swoole_server.h:1558-1562 coerces 0 to
            // UINT_MAX). Without a cap, slow upstreams cause unbounded
            // request/coroutine accumulation until OOM. 1000 is a generous
            // server-wide ceiling; tune to measured downstream capacity.
            Constant::OPTION_MAX_CONCURRENCY => 1_000,

            // Default: true (swoole_server.h:859). Restated explicitly:
            // SIGUSR1 reload drains in-flight work instead of cutting
            // connections. The default is correct, but explicit beats
            // accidental for deploy behaviour.
            Constant::OPTION_RELOAD_ASYNC => true,

            // Default: 0 → host CPU count (swoole_server.h:748). Host
            // detection is cgroup-blind: a 2-core pod on a 64-core node
            // spawns 64 reactor threads. System::getCpuNum() reads
            // cgroup limits before falling back to swoole_cpu_num().
            Constant::OPTION_REACTOR_NUM => (int) max(1, ceil(System::getCpuNum())),
        ];
    }

    /**
     * Get the Swoole settings for the given mode.
     *
     * @return array The settings array.
     */
    public function settings(): array
    {
        $settings = match ($this) {
            self::HYPERLOOP_A => [
                // Default: true (swoole_server.h:879). Off here so each
                // worker handles one request at a time; concurrency comes
                // from process count, not the scheduler.
                Constant::OPTION_ENABLE_COROUTINE => false,

                // Default: DISPATCH_FDMOD = 2 (swoole_server.h:759, enum at
                // 702-712). Mode 3 = DISPATCH_IDLE_WORKER (preemptive):
                // route to whichever worker is idle. Keeps utilisation
                // even when request times vary, at the cost of disabling
                // hash-dispatch features like send_yield.
                Constant::OPTION_DISPATCH_MODE => 3,

                // Default: 0 → host CPU count (swoole_server.h:752).
                // Without coroutines, workers block on I/O — we need more
                // processes than cores to keep CPUs busy. 6× assumes ~83%
                // I/O wait, typical for PHP web apps.
                Constant::OPTION_WORKER_NUM => (int) max(1, ceil(System::getCpuNum() * 6)),
            ],
            self::HYPERLOOP_B => [
                // Default: true (swoole_server.h:879). Restated; this
                // entire mode is built around coroutine concurrency.
                Constant::OPTION_ENABLE_COROUTINE => true,

                // Default: DISPATCH_FDMOD = 2. Restated because it's a
                // precondition for send_yield (master.cc:401-402 force-
                // disables yield outside hash-dispatch modes; eligible
                // modes are FDMOD/IPMOD/CO_CONN_LB per server.h:1290-93).
                // Bonus: per-worker DB/Redis pools stay attached.
                Constant::OPTION_DISPATCH_MODE => 2,

                // Default: true (swoole_server.h:875). Silently disabled
                // outside hash dispatch — we restate to make the
                // FDMOD pairing's intent explicit. Effect: a slow client
                // parks its coroutine instead of blocking the worker.
                Constant::OPTION_SEND_YIELD => true,

                // Default: 0 → host CPU count. With coroutines, one
                // worker holds thousands of parked requests; extra
                // processes don't buy concurrency, just memory and
                // context switches. 1× cores is the right shape.
                Constant::OPTION_WORKER_NUM => (int) max(1, ceil(System::getCpuNum())),

                // Default: host CPU × 8 (async_thread.cc:84 +
                // swoole_config.h:87 SW_AIO_THREAD_NUM_MULTIPLE = 8).
                // Read by Server::set() via swoole_server.cc:2034 →
                // swoole_async_coro.cc:43. We keep the 8× multiplier but
                // apply cgroup-aware base count to avoid 512 threads on
                // a 2-core pod.
                Constant::OPTION_AIO_WORKER_NUM => (int) max(1, ceil(System::getCpuNum() * 8)),

                // Default: 1 (async_thread.cc keeps a single warm
                // thread). Cold-start jitter on the first burst of file
                // I/O. Baseline = CPU count keeps a useful pool warm at
                // modest memory cost (a few hundred KB stack each).
                Constant::OPTION_AIO_CORE_WORKER_NUM => (int) max(1, ceil(System::getCpuNum())),
            ],
        };

        return [...self::defaults(), ...$settings];
    }
}
