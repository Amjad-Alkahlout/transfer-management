<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Tests\TestCase;

class OpenApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_document_is_valid_json_and_references_existing_components(): void
    {
        $contents = file_get_contents(base_path('docs/openapi.json'));
        $document = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertSame('/api/v1', $document['servers'][0]['url']);
        $this->assertNotEmpty($document['paths']);

        $references = [];
        $walk = function (mixed $value) use (&$walk, &$references): void {
            if (! is_array($value)) {
                return;
            }
            if (isset($value['$ref'])) {
                $references[] = $value['$ref'];
            }
            foreach ($value as $child) {
                $walk($child);
            }
        };
        $walk($document);

        foreach ($references as $reference) {
            $this->assertStringStartsWith('#/components/', $reference);
            $node = $document;
            foreach (explode('/', substr($reference, 2)) as $segment) {
                $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
                $this->assertArrayHasKey($segment, $node, "Unresolved OpenAPI reference {$reference}");
                $node = $node[$segment];
            }
        }
    }

    public function test_every_registered_api_v1_route_has_an_openapi_operation(): void
    {
        $document = json_decode(file_get_contents(base_path('docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $documentedPaths = $document['paths'];
        $httpMethods = ['get', 'post', 'put', 'patch', 'delete'];

        foreach (app('router')->getRoutes() as $route) {
            /** @var RoutingRoute $route */
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            $path = '/'.substr($route->uri(), strlen('api/v1/'));
            $path = preg_replace('/\{([^}:]+)(?::[^}]*)?\}/', '{$1}', $path);
            $this->assertArrayHasKey($path, $documentedPaths, "Undocumented route path {$route->uri()}");

            foreach (array_intersect($route->methods(), array_map('strtoupper', $httpMethods)) as $method) {
                $this->assertArrayHasKey(strtolower($method), $documentedPaths[$path], "Undocumented operation {$method} {$route->uri()}");
            }
        }
    }
}
