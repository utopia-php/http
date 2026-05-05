<?php

namespace Utopia\Http\Adapter\Swoole;

final class System
{
    /**
     * Get the fractional number of CPUs available to the process.
     *
     * Returns the raw quota (e.g. 1.5 for a 1500m cgroup limit) so callers
     * can decide whether to ceil, floor, or scale before clamping.
     *
     * @return float The number of CPUs available, possibly fractional.
     */
    public static function getCpuNum(): float
    {
        // cgroup v2: /sys/fs/cgroup/cpu.max -> "<quota> <period>" or "max <period>"
        if (is_readable('/sys/fs/cgroup/cpu.max')) {
            $line = trim((string) @file_get_contents('/sys/fs/cgroup/cpu.max'));
            if ($line !== '' && !str_starts_with($line, 'max')) {
                [$quota, $period] = array_pad(preg_split('/\s+/', $line), 2, null);
                $quota  = (float) $quota;
                $period = (float) $period;
                if ($quota > 0 && $period > 0) {
                    return $quota / $period;
                }
            }
        }
        // cgroup v1: cpu.cfs_quota_us / cpu.cfs_period_us
        $quotaPath  = '/sys/fs/cgroup/cpu/cpu.cfs_quota_us';
        $periodPath = '/sys/fs/cgroup/cpu/cpu.cfs_period_us';
        if (is_readable($quotaPath) && is_readable($periodPath)) {
            $quota  = (float) trim((string) @file_get_contents($quotaPath));
            $period = (float) trim((string) @file_get_contents($periodPath));
            if ($quota > 0 && $period > 0) {
                return $quota / $period;
            }
        }
        // macOS via sysctl
        if (PHP_OS_FAMILY === 'Darwin') {
            $out = @shell_exec('sysctl -n hw.ncpu 2>/dev/null');
            if ($out !== null) {
                $n = (float) trim($out);
                if ($n > 0) {
                    return $n;
                }
            }
        }
        // Linux /proc/cpuinfo (no cgroup, no Swoole extension loaded)
        if (is_readable('/proc/cpuinfo')) {
            $count = (float) preg_match_all('/^processor\s*:/m', (string) @file_get_contents('/proc/cpuinfo'));
            if ($count > 0) {
                return $count;
            }
        }
        // Swoole's own detector, if available
        if (function_exists('swoole_cpu_num')) {
            $n = (float) swoole_cpu_num();
            if ($n > 0) {
                return $n;
            }
        }
        // Last resort
        return 1.0;
    }
}
