<?php
namespace CFX\JsonApi\Test;

class TestData {
    public static function get($what=null) {
        $data = [];
        if (!$what || $what == 'errors') $data['errors'] = [
            [
                'status' => 400,
                'title' => 'Bad Parameter',
                'detail' => 'This request contains a bad parameter'
            ],
            [
                'status' => 409,
                'title' => 'Conflicted',
                'detail' => 'Some details',
            ]
        ];

        if (!$what || $what == 'data') $data['data'] = [
            [
                'type' => 'test1',
                'id' => '1',
                'attributes' => [
                    'city' => 'Chicago',
                    'state' => 'IL',
                    'country' => 'Panama',
                ],
                'relationships' => [
                    'owner' => [
                        'data' => [
                            'type' => 'people',
                            'id' => '2',
                        ],
                    ],
                    'inhabitants' => [
                        'data' => [
                            [
                                'type' => 'people',
                                'id' => '3',
                            ],
                            [
                                'type' => 'people',
                                'id' => '4',
                            ],
                            [
                                'type' => 'people',
                                'id' => '5',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'test1',
                'id' => '2',
                'attributes' => [
                    'city' => 'Boston',
                    'state' => 'MA',
                    'country' => 'Germany',
                ],
                'relationships' => [
                    'owner' => [
                        'data' => null
                    ],
                    'inhabitants' => [
                        'data' => []
                    ],
                ],
            ],
        ];

        if (!$what || $what == 'links') $data['links'] = [
            'self' => '/test/link',
            'next' => [
                'href' => '/test/link?pg=2',
                'meta' => [
                    'scheme' => 'paginated',
                ],
            ],
        ];

        return $data;
    }
}

