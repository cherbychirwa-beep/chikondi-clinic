<?php

if (! function_exists('money')) {
    /** Format an amount in the system currency (MWK by default). */
    function money(float|int|string|null $amount): string
    {
        return config('chikondi.currency', 'MWK').' '.number_format((float) $amount, 2);
    }
}
