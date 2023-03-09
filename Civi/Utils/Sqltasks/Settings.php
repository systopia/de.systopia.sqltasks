<?php

namespace Civi\Utils\Sqltasks;

use Civi;

class Settings {

    const SQLTASKS_MAX_FAILS_NUMBER = 'sqltasks_max_fails_number';

    const SQLTASKS_IS_DISPATCHER_DISABLED = 'sqltasks_is_dispatcher_disabled';

    public static function isDispatcherDisabled() {
        return Civi::settings()->get(Settings::SQLTASKS_IS_DISPATCHER_DISABLED) == 1;
    }

    public static function disableDispatcher() {
        Civi::settings()->set(Settings::SQLTASKS_IS_DISPATCHER_DISABLED, 1);
    }

    public static function enableDispatcher() {
        Civi::settings()->set(Settings::SQLTASKS_IS_DISPATCHER_DISABLED, 0);
    }

    public static function setIsDispatcherEnabled($isDispatcherEnabled) {
        Civi::settings()->set(Settings::SQLTASKS_IS_DISPATCHER_DISABLED, $isDispatcherEnabled);
    }

    public static function getMaxFailsNumber() {
        return (int) Civi::settings()->get(Settings::SQLTASKS_MAX_FAILS_NUMBER);
    }

    public static function setMaxFailsNumber($number) {
        if (is_numeric($number)) {
            Civi::settings()->set(Settings::SQLTASKS_MAX_FAILS_NUMBER, (int) $number);
        }
    }

}
