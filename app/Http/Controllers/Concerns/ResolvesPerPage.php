<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesPerPage
{
    /** Records per page, restricted to the configured options. */
    protected function perPage(Request $request): int
    {
        $options = config('chikondi.per_page_options');
        $value = (int) $request->input('per_page', config('chikondi.default_per_page'));

        return in_array($value, $options, true) ? $value : (int) config('chikondi.default_per_page');
    }
}
