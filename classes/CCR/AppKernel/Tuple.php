<?php

namespace CCR\AppKernel;

// ================================================================================
// An N-Tuple is a set of N ordered values.  Set up an N-Tuple that can be used
// to track when database results change.
// ================================================================================
// phpcs:disable PSR1.Classes.ClassDeclaration
class Tuple
{
    // phpcs:enable PSR1.Classes.ClassDeclaration
    private $size = 0;
    private $data = array();

    // --------------------------------------------------------------------------------
    // Set up the tuple and initialize the values to an array of NULL values.
    //
    // @param $size Number of elements in the tuple.
    // --------------------------------------------------------------------------------

    public function __construct($size)
    {
        $this->size = $size;
        $this->data = array_fill(0, $size, null);
    }

    // --------------------------------------------------------------------------------
    // Ensure that the number of parameters matches the size of the tuple and Set
    // the tuple values.
    //
    // @throws Exception of the number of arguments does not match the tuple size.
    // --------------------------------------------------------------------------------

    public function set()
    {
        if (func_num_args() != $this->size) {
            throw new \Exception("Not enough data elements");
        }
        $this->data = func_get_args();
    }
}
