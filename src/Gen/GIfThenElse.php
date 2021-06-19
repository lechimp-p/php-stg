<?php

namespace Lechimp\STG\Gen;

class GIfThenElse extends GStatement
{
    /**
     * @var string
     */
    protected $if;

    /**
     * @var GStatement[]
     */
    protected $then;

    /**
     * @var GStatement[]
     */
    protected $else;

    public function __construct($if, array $then, array $else)
    {
        assert(is_string($if));
        $this->if = $if;
        $this->then = array_map(function (GStatement $stmt) {
            return $stmt;
        }, $then);
        $this->else = array_map(function (GStatement $stmt) {
            return $stmt;
        }, $else);
    }

    /**
     * @inheritdoc
     */
    public function render($indentation)
    {
        assert(is_int($indentation));
        $if = $this->if;
        return implode("\n", array_merge(
            array( $this->indent($indentation, "if ($if) {")),
            array_map(function (GStatement $stmt) use ($indentation) {
                return $stmt->render($indentation + 1);
            }, $this->then),
            array( $this->indent($indentation, "}")
                , $this->indent($indentation, "else {")
                ),
            array_map(function (GStatement $stmt) use ($indentation) {
                return $stmt->render($indentation + 1);
            }, $this->else),
            array( $this->indent($indentation, "}") )
        ));
    }
}
