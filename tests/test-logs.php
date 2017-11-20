<?php

namespace Tainacan\Tests;

/**
 * Class TestCollections
 *
 * @package Test_Tainacan
 */

/**
 * Sample test case.
 */
class Logs extends \WP_UnitTestCase {


    /**
     * Teste da insercao de um log simples apenas se criar o dado bruto
     */
    function test_add() {
        global $Tainacan_Logs;

        $log = new \Tainacan\Entities\Log();

        //setando os valores na classe do tainacan
        $log->set_title('blame someone');
        $log->set_description('someone did that');

        //inserindo
        $log = $Tainacan_Logs->insert($log);

        //retorna a taxonomia
        $test = $Tainacan_Logs->fetch($log->get_id());

        $this->assertEquals( 'blame someone', $test->get_title() );
        $this->assertEquals( 'someone did that', $test->get_description() );
    }

}