<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\RouteListCommand as ArtisanRouteListCommand;

class RouteListCommand extends ArtisanRouteListCommand {

	public function handle() {

		if (count($this->routes) === 0) {

			$this->error("Your application doesn't have any routes.");
			return;
		}

		$routes = $this->getRoutes();
		array_walk($routes, function (&$value) {

			unset($value['host']);

			$closure_count = 0;
			$middleware = explode(',', $value['middleware']);
			array_shift($middleware); // remove "web"

			foreach ($middleware as $index => $name) {
				if ($name === 'Closure') {

					$closure_count++;
					unset($middleware[$index]);
				}
			}

			$middleware = implode(',', $middleware);

			if ($closure_count) {
				if ($closure_count > 1) {
					$middleware = sprintf("%s,%dxClosure", $middleware, $closure_count);
				} else {
					$middleware = $middleware ? sprintf("%s,Closure", $middleware) : "Closure";
				}
			}

			$value['middleware'] = $middleware;
			$value['action'] = str_replace('App\\Http\\Controllers\\', '', $value['action']);
		});

		array_shift($this->headers);
		$this->displayRoutes($routes);
	}
}
