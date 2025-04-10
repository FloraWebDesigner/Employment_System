<?php
require __DIR__.'../vendor/autoload.php';

$openapi = new OpenApi\Annotations\OpenApi([
    'info' => new OpenApi\Annotations\Info([
        'title' => "Working API",
        'version' => "1.0.0"
    ]),
    'paths' => [
        new OpenApi\Annotations\PathItem([
            'path' => '/employee.php',
            'get' => new OpenApi\Annotations\Get([
                'responses' => [
                    '200' => new OpenApi\Annotations\Response([
                        'description' => 'Success',
                        'content' => new OpenApi\Annotations\MediaType([
                            'mediaType' => 'application/json',
                            'schema' => new OpenApi\Annotations\Schema([
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string']
                                ]
                            ])
                        ])
                    ])
                ]
            ])
        ])
    ]
]);

file_put_contents('swagger.json', json_encode($openapi, JSON_PRETTY_PRINT));
echo "Successfully generated complete swagger.json\n";