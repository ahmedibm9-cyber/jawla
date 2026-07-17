import http from 'k6/http';
import { check } from 'k6';

const BASE = 'http://localhost:8000';

export default function () {
  // Step 1: GET login page to get CSRF token and session cookie
  let r1 = http.get(`${BASE}/admin/login`);
  let csrf = r1.body.match(/<meta name="csrf-token" content="([^"]+)"/);
  check(r1, { 'login page 200': (x) => x.status === 200 });
  console.log('CSRF token found:', csrf ? 'YES' : 'NO');

  // Step 2: POST with credentials
  let r2 = http.post(`${BASE}/admin/login`, {
    _token: csrf ? csrf[1] : '',
    email: 'admin@jawla.test',
    password: 'password',
  }, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });

  check(r2, {
    'POST status is 302': (x) => x.status === 302,
    'POST status is 200': (x) => x.status === 200,
  });
  console.log('POST response status:', r2.status);
  console.log('POST response URL:', r2.url);
  if (r2.status === 200) {
    let errorMsg = r2.body.match(/fi-fo-field-wrp-error-message[^>]*>([^<]+)/);
    if (errorMsg) console.log('Error:', errorMsg[1]);
  }

  // Step 3: Try dashboard after login
  let r3 = http.get(`${BASE}/admin`);
  check(r3, { 'dashboard 200': (x) => x.status === 200 });
  console.log('Dashboard status:', r3.status);
  console.log('Dashboard title match:', r3.body.includes('لوحة التحكم'));
}
