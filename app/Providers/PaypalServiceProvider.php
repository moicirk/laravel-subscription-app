<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

class PaypalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PaypalServerSdkClient::class, function ($app) {
            return PaypalServerSdkClientBuilder::init()
                ->clientCredentialsAuthCredentials(
                    ClientCredentialsAuthCredentialsBuilder::init(
                        'OAuthClientId',
                        'OAuthClientSecret'
                    )
                )
                ->environment(Environment::SANDBOX)
//                ->loggingConfiguration(
//                    LoggingConfigurationBuilder::init()
//                        ->level(LogLevel::INFO)
//                        ->requestConfiguration(RequestLoggingConfigurationBuilder::init()->body(true))
//                        ->responseConfiguration(ResponseLoggingConfigurationBuilder::init()->headers(true))
//                )
                ->build();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
