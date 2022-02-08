<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\JsonApi\Testing\Constraints;

use CloudCreativity\JsonApi\Testing\Compare;
use CloudCreativity\JsonApi\Testing\Document;
use CloudCreativity\JsonApi\Testing\Utils\JsonStack;
use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;

/**
 * Class SubsetsInOrder
 *
 * @package CloudCreativity\JsonApi\Testing
 */
class SubsetsInOrder extends Constraint
{

    /**
     * @var array
     */
    protected array $expected;

    /**
     * @var string
     */
    protected string $pointer;

    /**
     * @var bool
     */
    protected bool $strict;

    /**
     * SubsetsInOrder constructor.
     *
     * @param iterable $expected
     *      the expected object
     * @param string $pointer
     *      the JSON pointer to the object in the JSON API document.
     * @param bool $strict
     */
    public function __construct(iterable $expected, string $pointer, bool $strict = true)
    {
        $this->expected = JsonStack::cast($expected)->toArray();
        $this->pointer = $pointer;
        $this->strict = $strict;
    }

    /**
     * @inheritdoc
     */
    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        $actual = Document::cast($other)->get($this->pointer);
        $result = $this->compare($this->expected, $actual, $this->strict);

        if ($returnResult) {
            return $result;
        }

        if (!$result) {
            $this->fail(
                $other,
                $description,
                $this->failure($actual)
            );
        }

        return null;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return Compare::stringify($this->expected);
    }

    /**
     * @inheritdoc
     */
    protected function failureDescription($document): string
    {
        return "the array at [{$this->pointer}] contains the subsets in order:" . PHP_EOL
            . $this->toString() . PHP_EOL . PHP_EOL
            . "within JSON API document:" . PHP_EOL
            . Document::cast($document);
    }

    /**
     * @param array $expected
     * @param mixed $actual
     * @param bool $strict
     * @return bool
     */
    protected function compare(array $expected, $actual, bool $strict): bool
    {
        return Compare::subset($expected, $actual, $strict);
    }

    /**
     * @param mixed $actual
     * @return ComparisonFailure
     */
    protected function failure($actual): ComparisonFailure
    {
        return Compare::failure($this->expected, $actual, true);
    }

}
