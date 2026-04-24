import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:9501';

export const options = {
    scenarios: {
        warmup: {
            executor: 'constant-vus',
            vus: 10,
            duration: '5s',
            gracefulStop: '0s',
            tags: { phase: 'warmup' },
            exec: 'hit',
        },
        load: {
            executor: 'constant-vus',
            vus: 50,
            duration: '20s',
            startTime: '5s',
            gracefulStop: '2s',
            tags: { phase: 'load' },
            exec: 'hit',
        },
    },
    thresholds: {
        'http_req_failed{phase:load}': ['rate<0.001'],
        'http_req_duration{phase:load}': ['p(95)<50', 'p(99)<100'],
    },
};

const routes = ['/', '/value/hello', '/humans.txt', '/chunked'];

export function hit() {
    const path = routes[Math.floor(Math.random() * routes.length)];
    const res = http.get(`${BASE_URL}${path}`, { tags: { route: path } });
    check(res, { 'status is 2xx': (r) => r.status >= 200 && r.status < 300 });
}
