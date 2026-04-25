#!/bin/bash
cd domains/gxbeyblade.com/public_html
php artisan tinker --execute="echo DB::table('league_players')->select('id','blader_name','real_name')->get()->toJson(JSON_PRETTY_PRINT);"
