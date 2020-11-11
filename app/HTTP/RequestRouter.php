<?php

namespace app\HTTP;

class RequestRouter
{
    /**
     * The routing table, resolves paths to callables.
     * This is a recursive array, with each step representing a level of path-based routing.
     *
     * Keys represent sub-levels, and match the path.
     * Key "_" always resolves to a final closure and represents a routing result.
     * Key "$" denotes a variable sub-level name.
     *
     * Example:
     *  When registering a route "/users/$id/edit", the result is as follows:
     *
     *  ["users" => [
     *    "$" => [
     *      "edit" => [
     *        "_" => [RouteTarget for GET, RouteTarget for POST, ...]
     *      ]
     *    ]
     *  ]
     *
     * @var callable[]
     */
    protected array $routes = [];

    /**
     * Configures a $routeTarget for a given $path.
     *
     * @param string $path The path of the request URI, optionally with variables, e.g. "/user/$id/edit".
     * @param callable $routeTarget The route target to register.
     * @return $this
     */
    public function register(string $path, callable $routeTarget): self
    {
        $pathParts = explode('/', $path);
        array_shift($pathParts); // First item in path parts should be an empty string because of the "/"

        $routesStep = &$this->routes;

        foreach ($pathParts as $pathPart) {
            $isVariablePart = (strpos($pathPart, '$') === 0);

            if ($isVariablePart) {
                if (!isset($routesStep['$'])) {
                    $routesStep['$'] = [];
                }

                $routesStep['$']['__name'] = substr($pathPart, 1); // variable name without $
                $routesStep = &$routesStep['$'];
                continue;
            } else {
                if (!isset($routesStep[$pathPart])) {
                    $routesStep[$pathPart] = [];
                }

                $routesStep = &$routesStep[$pathPart];
                continue;
            }
        }

        $routesStep['_'] = $routeTarget;
        return $this;
    }

    protected function route(string $path, array &$_variables): ?callable
    {
        if ($path !== "/" && substr($path, -1) === '/') {
            // Remove trailing slash if we have one
            $path = substr($path, 0, -1);
        }

        $pathParts = explode('/', $path);

        // First item in path parts should be an empty string because of the "/", so remove it now.
        //  (NB: HttpRequest will never give us a path that does not start with "/".)
        array_shift($pathParts);

        $routesStep = $this->routes;

        foreach ($pathParts as $pathPart) {
            $exactMatch = $routesStep[$pathPart] ?? null;
            $variableMatch = $routesStep["$"] ?? null;

            if ($exactMatch || $variableMatch) {
                // We got a matching group, so continue but prefer exact matches
                if ($exactMatch) {
                    $routesStep = $exactMatch;
                } else {
                    $routesStep = $variableMatch;
                    $_variables[$routesStep['__name']] = $pathPart;
                }
                continue;
            }

            // Neither exact nor variable match; hard routing failure
            return null;
        }

        return $routesStep["_"] ?? null;
    }

    public function dispatch(IncomingRequest $request)
    {
        $_variables = [$request];
        $callable = $this->route($request->path, $_variables);

        if ($callable) {
            return call_user_func_array($callable, $_variables);
        } else {
            // 404 Not Found
            http_response_code(404);
            return null;
        }
    }
}