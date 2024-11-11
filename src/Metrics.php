<?php

namespace Utopia;

use Utopia\Telemetry\Adapter as Telemetry;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;

class Metrics
{
    private Histogram $requestDuration;
    private UpDownCounter $activeRequests;
    private Histogram $requestBodySize;
    private Histogram $responseBodySize;

    public function __construct(Telemetry $telemetry)
    {
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverrequestduration
        $this->requestDuration = $telemetry->createHistogram(
            'http.server.request.duration',
            's',
            null,
            ['ExplicitBucketBoundaries' =>  [0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1, 2.5, 5, 7.5, 10]]
        );

        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserveractive_requests
        $this->activeRequests = $telemetry->createUpDownCounter('http.server.active_requests', '{request}');
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverrequestbodysize
        $this->requestBodySize = $telemetry->createHistogram('http.server.request.body.size', 'By');
        // https://opentelemetry.io/docs/specs/semconv/http/http-metrics/#metric-httpserverresponsebodysize
        $this->responseBodySize = $telemetry->createHistogram('http.server.response.body.size', 'By');
    }

    public function recordMetrics(Request $request, Response $response, ?Route $route, float $requestDuration): void
    {
        $attributes = [
            'url.scheme' => $request->getProtocol(),
            'http.request.method' => $request->getMethod(),
            'http.route' => $route?->getPath(),
            'http.response.status_code' => $response->getStatusCode(),
        ];
        $this->requestDuration->record($requestDuration, $attributes);
        $this->requestBodySize->record($request->getSize(), $attributes);
        $this->responseBodySize->record($response->getSize(), $attributes);
    }

    public function increaseActiveRequest(Request $request): void
    {
        $this->activeRequests->add(1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);
    }

    public function decreaseActiveRequest(Request $request): void
    {
        $this->activeRequests->add(-1, [
            'http.request.method' => $request->getMethod(),
            'url.scheme' => $request->getProtocol(),
        ]);
    }
}
