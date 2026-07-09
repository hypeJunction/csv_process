<?php

namespace CsvProcess;

use Elgg\Event;
use Elgg\UnitTestCase;

/**
 * Pure-logic coverage for the demo callback handler.
 *
 * Regression anchor (migration fix 79f320b, 5.x hook->event unification):
 * DemoHandler::register now receives an \Elgg\Event (not the removed
 * \Elgg\Hook) and must return the callbacks map with its own callable merged
 * in without clobbering entries contributed by other plugins.
 */
class DemoHandlerTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	protected function makeCallbacksEvent($value): Event {
		$event = $this->getMockBuilder(Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getName')->willReturn('callbacks');
		$event->method('getType')->willReturn('csv_process');
		$event->method('getValue')->willReturn($value);

		return $event;
	}

	public function testRegisterMergesHandlerPreservingExistingCallbacks(): void {
		$existing = ['Other\\Plugin::process' => 'Other processor'];

		$result = DemoHandler::register($this->makeCallbacksEvent($existing));

		$this->assertIsArray($result);
		$this->assertArrayHasKey(
			'Other\\Plugin::process',
			$result,
			'register() clobbered a callback contributed by another plugin'
		);
		$this->assertSame('Other processor', $result['Other\\Plugin::process']);
		$this->assertArrayHasKey(
			DemoHandler::class . '::handle',
			$result,
			'register() did not add its own handler to the callbacks map'
		);
		$this->assertSame(
			\elgg_echo('csv_process:handler:label'),
			$result[DemoHandler::class . '::handle']
		);
	}

	public function testRegisterCastsScalarValueToArray(): void {
		// The event value defaults to [] but register() defensively casts;
		// a non-array value must not fatal and must still yield the handler.
		$result = DemoHandler::register($this->makeCallbacksEvent(null));

		$this->assertIsArray($result);
		$this->assertArrayHasKey(DemoHandler::class . '::handle', $result);
	}

	public function testRegisteredHandlerKeyIsCallable(): void {
		// The action gate does is_callable($callback) on the map KEY, so the
		// registered key must be a resolvable Class::method string callable.
		$result = DemoHandler::register($this->makeCallbacksEvent([]));

		$key = DemoHandler::class . '::handle';
		$this->assertContains($key, array_keys($result));
		$this->assertTrue(
			is_callable($key),
			"callbacks-map key '{$key}' is not callable — action's is_callable() gate would reject it"
		);
	}

	public function testHandleReturnsPerRowLine(): void {
		$line = DemoHandler::handle([
			'data' => ['Alpha', 'Beta'],
			'line' => 3,
			'last' => false,
		]);

		$this->assertSame('First Cell: Alpha, Line: 3', $line);
	}

	public function testHandleReturnsSummaryOnLastInvocation(): void {
		$summary = DemoHandler::handle([
			'data' => [],
			'line' => 42,
			'last' => true,
		]);

		$this->assertSame('Log some summary information... after 42 lines', $summary);
	}
}
