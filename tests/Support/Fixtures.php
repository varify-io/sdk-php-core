<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Support;

use Varify\Core\Config\Config;
use Varify\Core\Config\ConfigParser;

final class Fixtures
{
    public const VISITOR_ID = '3f2a9c1e-5b7d-4e8a-9c21-7d4e5f6a8b90';

    /**
     * Experiment 48812 splits 50/50 between 48838 and 48839; VISITOR_ID buckets at 56.007, so into 48839.
     * Experiment 269 has tracking disabled and one variation at 100.
     */
    public const CONFIG_JSON = <<<'JSON'
        {"experiments":[
          {"id":48812,"slug":"checkout-button","tracking_enabled":true,"variations":[
            {"id":48838,"slug":"blue","traffic_allocation":50},
            {"id":48839,"slug":"green","traffic_allocation":50}
          ]},
          {"id":269,"slug":"hero</script>","tracking_enabled":false,"variations":[
            {"id":311,"slug":"new-hero","traffic_allocation":100}
          ]}
        ]}
        JSON;

    public static function config(): Config
    {
        return ConfigParser::parse(self::CONFIG_JSON);
    }
}
