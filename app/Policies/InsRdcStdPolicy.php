<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class InsRdcStdPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function manage(User $user): Response
    {
        $auth = $user->ins_rdc_auths->first();
        $actions = json_decode($auth->actions ?? '{}', true);
        return in_array('std-manage', $actions)
        ? Response::allow()
        : Response::deny( __('Kamu tak memiliki wewenang untuk mengelola standar') );
    }

    public function before(User $user): bool|null
    {
        return $user->id == 1 ? true : null;
    }
}
