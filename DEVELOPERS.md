# Developers
This is a guide for software engineers who wish to take part in the development of this product.

## Environment Setup
This project declares all of its dependencies, and configures a Docker environment. Follow the
steps described below to set everything up.

1. Clone the repo, if you haven't already.
2. Copy `.env.example` to `.env`, and change relevant configuration if necessary.
3. Install dependencies with Composer.

    ```
    docker-compose run --rm build composer install
    ```

   Alternatively, use PHPStorm to install dependencies (requires step 2).
4. Build assets in the required Mollie Payments for Woocommerce plugin:

    ```
    yarn --cwd vendor/mollie/mollie-woocommerce install
    yarn --cwd vendor/mollie/mollie-woocommerce build
    ```
6. Bring up the environment.

    ```
    docker-compose up -d
    ```
