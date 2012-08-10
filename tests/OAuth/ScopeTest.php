<?php

require_once 'lib/OAuth/Scope.php';

class ScopeTest extends PHPUnit_Framework_TestCase {

    function testBasicScope() {
        $s = new Scope("read write delete");
        $this->assertTrue($s->isSubsetOf(new Scope("read write delete update")));
        $this->assertTrue($s->hasScope(new Scope("read")));
        $this->assertTrue($s->hasScope(new Scope("write")));
        $this->assertEquals("delete read write", $s->getScope());
    }

    function testEmptyScope() {
        $s = new Scope(NULL);
        $this->assertEquals("", $s->getScope());
        $t = new Scope("");
        $this->assertEquals("", $s->getScope());        
    }

    function testFailingScope() {
        $s = new Scope("read write delete");
        $this->assertFalse($s->isSubsetOf(new Scope("read write update")));
        $this->assertFalse($s->hasScope(new Scope("foo")));
    }

    /**
     * @expectedException ScopeException
     */
    function testMalformedScope() {
        $s = new Scope(" ");
    }

    function testMerge() {
        $s = new Scope("read write delete merge");
        $t = new Scope("write delete");
        $s->mergeWith($t);
        $this->assertEquals("delete merge read write", $s->getScope());
    }

    function testClone() {
        $s = new Scope("read write delete merge");
        $t = new Scope("write delete");
        $c = clone $s;
        $c->mergeWith($t);
        $this->assertEquals("delete merge read write", $c->getScope());
    }

}

?>
