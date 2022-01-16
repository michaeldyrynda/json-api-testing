<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\JsonApi\Testing\Tests\Assertions;

use Closure;
use CloudCreativity\JsonApi\Testing\HttpMessage;
use CloudCreativity\JsonApi\Testing\Tests\TestCase;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Collection;

class FetchedToManyTest extends TestCase
{

    /**
     * @var array
     */
    private array $post1 = [
        'type' => 'posts',
        'id' => '1',
    ];

    /**
     * @var array
     */
    private array $post2 = [
        'type' => 'posts',
        'id' => '2',
    ];

    /**
     * @var array
     */
    private array $post3 = [
        'type' => 'posts',
        'id' => '3',
    ];

    /**
     * @var HttpMessage
     */
    private HttpMessage $http;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new HttpMessage(
            200,
            'application/vnd.api+json',
            json_encode(['data' => [$this->post1, $this->post2, $this->post3]]),
            ['Content-Type' => 'application/vnd.api+json', 'Accept' => 'application/vnd.api+json'],
        );

        $this->http->willSeeType($this->post1['type']);
    }

    public function testFetchedToManyWithUrlRoutables(): void
    {
        $model1 = $this->createMock(UrlRoutable::class);
        $model1->method('getRouteKey')->willReturn((int) $this->post1['id']);

        $model2 = $this->createMock(UrlRoutable::class);
        $model2->method('getRouteKey')->willReturn((int) $this->post2['id']);

        $model3 = $this->createMock(UrlRoutable::class);
        $model3->method('getRouteKey')->willReturn((int) $this->post3['id']);

        $invalid = $this->createMock(UrlRoutable::class);
        $invalid->method('getRouteKey')->willReturn((int) ($this->post3['id'] + 1));

        $models = [$model2, $model1, $model3]; // order is not asserted.

        $this->http->assertFetchedToMany($models);
        $this->http->assertFetchedToMany(Collection::make($models));

        $this->assertThatItFails(
            'the document has an empty list at [/data]',
            fn() => $this->http->assertFetchedToMany([]),
        );

        $this->assertThatItFails(
            'the list at [/data] only contains the values',
            fn() => $this->http->assertFetchedToMany([$model1, $model3]),
        );

        $this->assertThatItFails(
            'the list at [/data] only contains the values',
            fn() => $this->http->assertFetchedToMany([$model1, $invalid, $model3]),
        );
    }

    public function testFetchedToManyWithIntegers(): void
    {
        $id1 = (int) $this->post1['id'];
        $id2 = (int) $this->post2['id'];
        $id3 = (int) $this->post3['id'];
        $invalid = $id3 + 1;

        $ids = [$id2, $id1, $id3]; // order is not asserted.

        $this->http->assertFetchedToMany($ids);
        $this->http->assertFetchedToMany(Collection::make($ids));

        $this->assertThatItFails(
            'the document has an empty list at [/data]',
            fn() => $this->http->assertFetchedToMany([]),
        );

        $this->assertThatItFails(
            'the list at [/data] only contains the values',
            fn() => $this->http->assertFetchedToMany([$id1, $id3]),
        );

        $this->assertThatItFails(
            'the list at [/data] only contains the values',
            fn() => $this->http->assertFetchedToMany([$id1, $invalid, $id3]),
        );
    }

    public function testFetchedToManyWithStrings(): void
    {
        $id1 = $this->post1['id'];
        $id2 = $this->post2['id'];
        $id3 = $this->post3['id'];
        $invalid = strval($id3 + 1);

        $ids = [$id2, $id1, $id3]; // order is not asserted.

        $this->http->assertFetchedToMany($ids);
        $this->http->assertFetchedToMany(Collection::make($ids));

        $this->assertThatItFails(
            'the document has an empty list at [/data]',
            fn() => $this->http->assertFetchedToMany([]),
        );

        $this->assertThatItFails(
            'the list at [/data] only contains the values',
            fn() => $this->http->assertFetchedToMany([$id1, $id3]),
        );

        $this->assertThatItFails(
            'the list at [/data] only contains the values',
            fn() => $this->http->assertFetchedToMany([$id1, $invalid, $id3]),
        );
    }

    /**
     * @return array[]
     */
    public function fetchedToManyArrayProvider(): array
    {
        return [
            'in order' => [
                true,
                fn(array $post1, array $post2, array $post3): array => [
                    $post1,
                    $post2,
                    $post3,
                ],
            ],
            'not in order' => [
                true,
                fn(array $post1, array $post2, array $post3): array => [
                    $post3,
                    $post1,
                    $post2,
                ],
            ],
            'invalid type' => [
                false,
                fn(array $post1, array $post2, array $post3): array => [
                    $post1,
                    ['type' => 'foobar', 'id' => $post2['id']],
                    $post3,
                ],
            ],
            'invalid id' => [
                false,
                fn(array $post1, array $post2, array $post3): array => [
                    $post1,
                    ['type' => $post2['type'], 'id' => strval($post2['id'] * 10)],
                    $post3,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param Closure $provider
     * @return void
     * @dataProvider fetchedToManyArrayProvider
     */
    public function testFetchedToManyWithArray(bool $expected, Closure $provider): void
    {
        $value = $provider($this->post1, $this->post2, $this->post3);

        if ($expected) {
            $this->http->assertFetchedToMany($value);
        } else {
            $this->assertThatItFails(
                'the list at [/data] only contains the values',
                fn() => $this->http->assertFetchedToMany($value)
            );
        }
    }

    public function testFetchedToManyWithResources(): void
    {
        [$post1, $post2, $post3] = $this->createResources();

        $http = $this->http->withContent(json_encode(['data' => [$post1, $post2, $post3]]));

        $this->assertThatItFails(
            'list at [/data] only contains the values',
            fn() => $http->assertFetchedToMany([$this->post1, $this->post2, $this->post3]),
        );
    }

    public function testFetchedToManyInOrderWithUrlRoutables(): void
    {
        $model1 = $this->createMock(UrlRoutable::class);
        $model1->method('getRouteKey')->willReturn((int) $this->post1['id']);

        $model2 = $this->createMock(UrlRoutable::class);
        $model2->method('getRouteKey')->willReturn((int) $this->post2['id']);

        $model3 = $this->createMock(UrlRoutable::class);
        $model3->method('getRouteKey')->willReturn((int) $this->post3['id']);

        $invalid = $this->createMock(UrlRoutable::class);
        $invalid->method('getRouteKey')->willReturn((int) ($this->post3['id'] + 1));

        $models = [$model1, $model2, $model3];

        $this->http->assertFetchedToManyInOrder($models);
        $this->http->assertFetchedToManyInOrder(Collection::make($models));

        $this->assertThatItFails(
            'the document has an empty list at [/data]',
            fn() => $this->http->assertFetchedToManyInOrder([]),
        );

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $this->http->assertFetchedToManyInOrder([$model1, $model3, $model2]),
        );

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $this->http->assertFetchedToManyInOrder([$model1, $invalid, $model3]),
        );
    }

    public function testFetchedToManyInOrderWithIntegers(): void
    {
        $id1 = (int) $this->post1['id'];
        $id2 = (int) $this->post2['id'];
        $id3 = (int) $this->post3['id'];
        $invalid = $id3 + 1;

        $ids = [$id1, $id2, $id3];

        $this->http->assertFetchedToManyInOrder($ids);
        $this->http->assertFetchedToManyInOrder(Collection::make($ids));

        $this->assertThatItFails(
            'the document has an empty list at [/data]',
            fn() => $this->http->assertFetchedToManyInOrder([]),
        );

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $this->http->assertFetchedToManyInOrder([$id1, $id3, $id2]),
        );

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $this->http->assertFetchedToManyInOrder([$id1, $invalid, $id3]),
        );
    }

    public function testFetchedToManyInOrderWithStrings(): void
    {
        $id1 = $this->post1['id'];
        $id2 = $this->post2['id'];
        $id3 = $this->post3['id'];
        $invalid = strval($id3 + 1);

        $ids = [$id1, $id2, $id3];

        $this->http->assertFetchedToManyInOrder($ids);
        $this->http->assertFetchedToManyInOrder(Collection::make($ids));

        $this->assertThatItFails(
            'the document has an empty list at [/data]',
            fn() => $this->http->assertFetchedToManyInOrder([]),
        );

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $this->http->assertFetchedToManyInOrder([$id1, $id3, $id2]),
        );

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $this->http->assertFetchedToManyInOrder([$id1, $invalid, $id3]),
        );
    }

    /**
     * @return array[]
     */
    public function fetchedToManyInOrderArrayProvider(): array
    {
        return [
            'in order' => [
                true,
                fn(array $post1, array $post2, array $post3): array => [
                    $post1,
                    $post2,
                    $post3,
                ],
            ],
            'not in order' => [
                false,
                fn(array $post1, array $post2, array $post3): array => [
                    $post3,
                    $post1,
                    $post2,
                ],
            ],
            'invalid type' => [
                false,
                fn(array $post1, array $post2, array $post3): array => [
                    $post1,
                    ['type' => 'foobar', 'id' => $post2['id']],
                    $post3,
                ],
            ],
            'invalid id' => [
                false,
                fn(array $post1, array $post2, array $post3): array => [
                    $post1,
                    ['type' => $post2['type'], 'id' => strval($post2['id'] * 10)],
                    $post3,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param Closure $provider
     * @return void
     * @dataProvider fetchedToManyInOrderArrayProvider
     */
    public function testFetchedToManyInOrderWithArray(bool $expected, Closure $provider): void
    {
        $value = $provider($this->post1, $this->post2, $this->post3);

        if ($expected) {
            $this->http->assertFetchedToManyInOrder($value);
        } else {
            $this->assertThatItFails(
                'member at [/data] matches the resource identifiers',
                fn() => $this->http->assertFetchedToManyInOrder($value)
            );
        }
    }

    public function testFetchedToManyInOrderWithResources(): void
    {
        [$post1, $post2, $post3] = $this->createResources();

        $http = $this->http->withContent(json_encode(['data' => [$post1, $post2, $post3]]));

        $this->assertThatItFails(
            'member at [/data] matches the resource identifiers',
            fn() => $http->assertFetchedToManyInOrder([$this->post1, $this->post2, $this->post3]),
        );
    }

    public function testInvalidStatusCode(): void
    {
        $http = $this->http->withStatusCode(201);
        $expected = [$this->post1, $this->post2, $this->post3];

        $this->assertThatItFails(
            'status 201 is 200',
            fn() => $http->assertFetchedToMany($expected)
        );

        $this->assertThatItFails(
            'status 201 is 200',
            fn() => $http->assertFetchedToManyInOrder($expected)
        );
    }

    public function testInvalidContentType(): void
    {
        $http = $this->http->withContentType('application/json');
        $expected = [$this->post1, $this->post2, $this->post3];

        $this->assertThatItFails(
            'media type',
            fn() => $http->assertFetchedToMany($expected)
        );

        $this->assertThatItFails(
            'media type',
            fn() => $http->assertFetchedToManyInOrder($expected)
        );
    }

    /**
     * @return array[]
     */
    private function createResources(): array
    {
        $post1 = [
            'type' => $this->post1['type'],
            'id' => $this->post1['id'],
            'attributes' => [
                'foo' => 'bar',
            ],
            'relationships' => [
                'baz' => [
                    'data' => null,
                ],
            ],
            'links' => [
                'self' => sprintf('/api/v1/%s/%d', $this->post1['type'], $this->post1['id'])
            ],
        ];

        $post2 = [
            'type' => $this->post2['type'],
            'id' => $this->post2['id'],
            'attributes' => [
                'foo' => 'bar',
            ],
        ];

        $post3 = [
            'type' => $this->post1['type'],
            'id' => $this->post1['id'],
            'relationships' => [
                'baz' => [
                    'data' => null,
                ],
            ],
        ];

        return [$post1, $post2, $post3];
    }
}
