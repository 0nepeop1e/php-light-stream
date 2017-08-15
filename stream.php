<?php
/**
 * Very very lightweight stream
 */
namespace LightStream {

    use \Iterator;
    use \Generator;
    use \IteratorAggregate;
    use \Traversable;
    use \TypeError;

    /**
     * Class Stream
     * @package LightStream
     */
    class Stream implements Iterator
    {
        //region Static utils
        /**
         * Create a stream from iterable or a
         * single value stream from none iterable.
         * @param mixed $any anything.
         * @return Stream the stream.
         */
        public static function of($any)
        {
            return new self($any);
        }

        /**
         * Create a stream which repeat n-times, like
         * Ruby's Integer#times. Basically useless.
         * @param int $nTimes the number of times
         * @return Stream the stream.
         * @throws TypeError when any type error.
         */
        public static function nTimes($nTimes)
        {
            if (!is_numeric($nTimes))
                throw new TypeError('$nTimes is not a number.');
            return new self(self::_nTimes(intval($nTimes)));
        }

        /**
         * Create a steam with infinity null.
         * Basically very useless, be careful
         * for the infinity loop.
         * @return Stream the stream.
         */
        public static function infinite()
        {
            return new self(self::_infinite());
        }

        /**
         * Create a stream with a range
         * @param int $from start value
         * @param int $to end value
         * @param int $step step value, auto correct when its not stepping to end value.
         * @return Stream the stream.
         * @throws TypeError when any type error.
         */
        public static function range($from, $to, $step = 1)
        {
            if (!is_numeric($from))
                throw new TypeError('$from is not a number.');
            if (!is_numeric($to))
                throw new TypeError('$to is not a number.');
            if (!is_numeric($step))
                throw new TypeError('$step is not a number.');
            $from = self::_num_val($from);
            $to = self::_num_val($to);
            $step = max(1, abs(self::_num_val($step)));
            if ($to < $from) $step = -$step;
            return new self(self::_range($from, $to, $step));
        }

        /**
         * Check whether the provided value is iterable or not.
         * @param mixed $any the value.
         * @return bool true when its iterable.
         */
        public static function isIterable($any)
        {
            return function_exists('is_iterable') ?
                is_iterable($any) :
                is_array($any) || $any instanceof Traversable;
        }
        //endregion

        /**
         * @var Iterator Internal iterator.
         */
        private $iterable;

        /**
         * Stream constructor.
         * @param mixed $any any value.
         */
        private function __construct($any)
        {
            if ($any instanceof Stream) {
                $this->iterable = $any->iterable;
            } elseif ($any instanceof Iterator) {
                $this->iterable = $any;
            } elseif ($any instanceof IteratorAggregate) {
                $this->iterable = $any->getIterator();
            } elseif (self::isIterable($any)) {
                $this->iterable = self::_wrap($any);
            } else {
                $this->iterable = self::_one($any);
            }
        }

        //region Implemented interface functions

        /**
         * Return the current element
         * @link http://php.net/manual/en/iterator.current.php
         * @return mixed Can return any type.
         * @since 5.0.0
         */
        public function current()
        {
            return $this->iterable->current();
        }

        /**
         * Move forward to next element
         * @link http://php.net/manual/en/iterator.next.php
         * @return void Any returned value is ignored.
         * @since 5.0.0
         */
        public function next()
        {
            $this->iterable->next();
        }

        /**
         * Return the key of the current element
         * @link http://php.net/manual/en/iterator.key.php
         * @return mixed scalar on success, or null on failure.
         * @since 5.0.0
         */
        public function key()
        {
            return $this->iterable->key();
        }

        /**
         * Checks if current position is valid
         * @link http://php.net/manual/en/iterator.valid.php
         * @return boolean The return value will be casted to boolean and then evaluated.
         * Returns true on success or false on failure.
         * @since 5.0.0
         */
        public function valid()
        {
            return $this->iterable->valid();
        }

        /**
         * Rewind the Iterator to the first element
         * @link http://php.net/manual/en/iterator.rewind.php
         * @return void Any returned value is ignored.
         * @since 5.0.0
         */
        public function rewind()
        {
            $this->iterable->rewind();
        }
        //endregion

        /**
         * @param callable $mapper
         * @return Stream
         * @throws TypeError
         */
        public function map($mapper)
        {
            if (!is_callable($mapper))
                throw new TypeError('$mapper is not callable');
            return new self(self::_map($this->iterable, $mapper));
        }

        /**
         * @param callable $predicate
         * @return Stream
         * @throws TypeError
         */
        public function filter($predicate)
        {
            if (!is_callable($predicate))
                throw new TypeError('$predicate is not callable');
            return new self(self::_filter($this->iterable, $predicate));
        }

        /**
         * @param int $amount
         * @return Stream
         * @throws TypeError
         */
        public function skip($amount)
        {
            if (!is_numeric($amount))
                throw new TypeError('$amount is not a number.');
            return new self(self::_skip($this->iterable, intval($amount)));
        }

        /**
         * @param int $amount
         * @return Stream
         * @throws TypeError
         */
        public function limit(int $amount)
        {
            if (!is_numeric($amount))
                throw new TypeError('$amount is not a number.');
            return new self(self::_limit($this->iterable, intval($amount)));
        }

        /**
         * @return Stream
         */
        public function unPair()
        {
            return new self(self::_unPair($this->iterable));
        }

        /**
         * @param callable $keyProvider
         * @return Stream
         * @throws TypeError
         */
        public function pair($keyProvider)
        {
            if (!is_callable($keyProvider))
                throw new TypeError('$keyProvider is not callable.');
            return new self(self::_pair($this->iterable, $keyProvider));
        }

        /**
         * @param $groupProvider
         * @return Stream
         * @throws TypeError
         * @see Stream::pair()
         */
        public function group($groupProvider)
        {
            if (!is_callable($groupProvider))
                throw new TypeError('$groupProvider is not callable.');
            return new self($this->pair($groupProvider)->collectWithKeys());
        }

        /**
         * @param iterable[] ...$iterable
         * @return Stream
         */
        public function concat(...$iterable)
        {
            $iterable = Stream::of($iterable)->map(function ($i) {
                return (new self($i))->iterable;
            });
            return new self(self::_concat($this->iterable, $iterable->collect()));
        }

        /**
         * @return Stream
         */
        public function flatten()
        {
            return new self(self::_flatten($this->iterable));
        }

        /**
         * @return Stream
         */
        public function flattenToTheEnd()
        {
            return new self(self::_flattenToTheEnd($this->iterable));
        }

        /**
         * @param callable $operator
         * @param mixed $init
         * @return mixed
         * @throws TypeError
         */
        public function reduce($operator, $init)
        {
            if (!is_callable($operator))
                throw new TypeError('$operator is not callable');
            foreach ($this->iterable as $v)
                $init = call_user_func($operator, $init, $v);
            return $init;
        }

        /**
         * @param callable $predicate
         * @return bool
         */
        public function any($predicate = null)
        {
            if (is_callable($predicate)) {
                foreach ($this->iterable as $v) {
                    if (call_user_func($predicate, $v)) return true;
                }
            } else {
                foreach ($this->iterable as $v) {
                    if ($v) return true;
                }
            }
            return false;
        }

        /**
         * @param callable $predicate
         * @return bool
         */
        public function all($predicate = null)
        {
            if (is_callable($predicate)) {
                foreach ($this->iterable as $v) {
                    if (!call_user_func($predicate, $v)) return false;
                }
            } else {
                foreach ($this->iterable as $v) {
                    if (!$v) return false;
                }
            }
            return true;
        }

        /**
         * @return array
         */
        public function collect()
        {
            return iterator_to_array($this, false);
        }

        /**
         * @return array
         */
        public function collectWithKeys()
        {
            $res = [];
            foreach ($this as $key => $value) {
                if (!isset($res[$key]))
                    $res[$key] = [];
                array_push($res[$key], $value);
            }
            return $res;
        }

        /**
         * @param $any
         * @return float|int
         */
        private static function _num_val($any)
        {
            $val = floatval($any);
            if ($val - floor($val) == 0 && $val >= PHP_INT_MIN && $val <= PHP_INT_MAX)
                return intval($val);
            return $val;
        }

        //region Private wrappers

        /**
         * @param int $n
         * @return Generator
         */
        private static function _nTimes($n)
        {
            for ($i = 0; $i < $n; $i++)
                yield $i;
        }

        /**
         * @return Generator
         */
        private static function _infinite()
        {
            while (true) yield;
        }

        /**
         * @param int $f
         * @param int $t
         * @param int $s
         * @return Generator
         */
        private static function _range($f, $t, $s)
        {
            for (; ($f <=> $t) * $s < 0; $f += $s)
                yield $f;
        }

        /**
         * @param Iterator $i
         * @return Generator
         */
        private static function _wrap($i)
        {
            yield from $i;
        }

        /**
         * @param mixed $v
         * @return Generator
         */
        private static function _one($v)
        {
            yield 0 => $v;
        }

        /**
         * @param Iterator $i
         * @param callable $c
         * @return Generator
         */
        private static function _map($i, $c)
        {
            foreach ($i as $k => $v)
                yield $k => call_user_func($c, $v);
        }

        /**
         * @param Iterator $i
         * @param callable $c
         * @return Generator
         */
        private static function _filter($i, $c)
        {
            foreach ($i as $k => $v)
                if (call_user_func($c, $v))
                    yield $k => $v;
        }

        /**
         * @param Iterator $i
         * @param int $n
         * @return Generator
         */
        private static function _skip($i, $n)
        {
            for (; $n; $n--) $i->next();
            for (; $i->valid(); $i->next())
                yield $i->key() => $i->current();
        }

        /**
         * @param Iterator $i
         * @param int $n
         * @return Generator
         */
        private static function _limit($i, $n)
        {
            foreach ($i as $k => $v) {
                if (!$n--) break;
                yield $k => $v;
            }
        }

        /**
         * @param Iterator $i
         * @return Generator
         */
        private static function _unPair($i)
        {
            foreach ($i as $k => $v)
                yield (object)['key' => $k, 'value' => $v];
        }

        /**
         * @param Iterator $i
         * @param callable $c
         * @return Generator
         */
        private static function _pair($i, $c)
        {
            foreach ($i as $v)
                yield call_user_func($c, $v) => $v;
        }

        /**
         * @param Iterator $i
         * @param Iterator[] $is
         * @return Generator
         */
        private static function _concat($i, $is)
        {
            yield from $i;
            foreach ($is as $i)
                yield from $i;
        }

        /**
         * @param Iterator $i
         * @return Generator
         */
        private static function _flatten($i)
        {
            foreach ($i as $k => $v) {
                if (self::isIterable($v)) {
                    yield from $v;
                } else {
                    yield $k => $v;
                }
            }
        }

        /**
         * @param Iterator $i
         * @return Generator
         */
        private static function _flattenToTheEnd($i)
        {
            foreach ($i as $k => $v) {
                if (self::isIterable($v)) {
                    yield from self::_flattenToTheEnd($v);
                } else {
                    yield $k => $v;
                }
            }
        }
        //endregion
    }
}
