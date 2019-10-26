<?php

namespace App\Clients;


interface ClientInterface 
{

    /**
     * @param $id
     * @param $amount
     * @return array
     */
    public function provide($id, $amount);

}