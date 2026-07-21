<?php

namespace Tests\Unit;

use App\Support\SentryScrubber;
use PHPUnit\Framework\TestCase;
use Sentry\Event;

class SentryScrubberTest extends TestCase
{
    public function test_it_masks_sensitive_request_body_keys(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'data' => [
                'email' => 'rep@jawla.test',
                'password' => 'super-secret',
                'api_token' => 'abc123',
                'amount' => 500,
                'zatca_secret' => 'zzz',
            ],
        ]);

        $scrubbed = SentryScrubber::handle($event, null);
        $data = $scrubbed->getRequest()['data'];

        $this->assertSame('[filtered]', $data['password']);
        $this->assertSame('[filtered]', $data['api_token']);
        $this->assertSame('[filtered]', $data['zatca_secret']);
        // Non-sensitive fields pass through untouched.
        $this->assertSame('rep@jawla.test', $data['email']);
        $this->assertSame(500, $data['amount']);
    }

    public function test_it_masks_nested_and_cookie_and_extra_values(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'cookies' => ['XSRF-TOKEN' => 'leak'],
            'data' => ['user' => ['session_id' => 'sess', 'name' => 'Sara']],
        ]);
        $event->setExtra(['authorization' => 'Bearer x', 'note' => 'ok']);

        $scrubbed = SentryScrubber::handle($event, null);

        $this->assertSame('[filtered]', $scrubbed->getRequest()['cookies']);
        $this->assertSame('[filtered]', $scrubbed->getRequest()['data']['user']['session_id']);
        $this->assertSame('Sara', $scrubbed->getRequest()['data']['user']['name']);
        $this->assertSame('[filtered]', $scrubbed->getExtra()['authorization']);
        $this->assertSame('ok', $scrubbed->getExtra()['note']);
    }
}
