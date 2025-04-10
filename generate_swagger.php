<?php
// Use absolute path to autoloader
require __DIR__ . '/vendor/autoload.php';

// Manually create OpenAPI documentation
$openapi = new OpenApi\Annotations\OpenApi([
    'openapi' => '3.0.0',
    'info' => new OpenApi\Annotations\Info([
        'title' => 'Employee API',
        'version' => '1.0.0',
        'description' => 'API Documentation'
    ]),
    'paths' => [
        new OpenApi\Annotations\PathItem([
            'path' => '/employee.php',
            'get' => new OpenApi\Annotations\Get([
                'summary' => 'Get employee data',
                'responses' => [
                    '200' => new OpenApi\Annotations\Response([
                        'description' => 'Successful operation',
                        'content' => [
                            'application/json' => new OpenApi\Annotations\MediaType([
                                'schema' => new OpenApi\Annotations\Schema([
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'integer', 'example' => 1],
                                        'name' => ['type' => 'string', 'example' => 'John Doe']
                                    ]
                                ])
                            ])
                        ]
                    ])
                ]
            ])
        ])
    ]
]);

file_put_contents('swagger.json', $openapi->toJson());
echo "Successfully generated swagger.json\n";