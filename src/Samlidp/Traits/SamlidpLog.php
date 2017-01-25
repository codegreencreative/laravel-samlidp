<?php

namespace Codegreencreative\Idp\Traits;

trait SamlidpLog
{
    /**
     * [samlLog description]
     *
     * @param  string $text [description]
     * @return [type]       [description]
     */
    protected function samlLog($arg=null)
    {
        if (config('samlidp.debug') && ! is_null($arg) && ! is_object($arg)) {
            Log::info($arg);
        }
    }

}