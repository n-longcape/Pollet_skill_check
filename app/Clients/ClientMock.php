<?php

namespace App\Clients;


class ClientMock implements ClientInterface
{
    
    public function provide($id, $amount)
    {
        return json_decode(
            '{"success": true,"code":200}'
        );
    }

}