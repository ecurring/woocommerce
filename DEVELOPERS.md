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
   If this is the first time you setting up this environment the image will be built first. 
   This procedure doesn't require any additional actions from your side.

4. Build assets in the required Mollie Payments for Woocommerce plugin:

    ```
    yarn --cwd vendor/mollie/mollie-woocommerce install
    yarn --cwd vendor/mollie/mollie-woocommerce build
    ```
5. Add to your `hosts` file:
   
   ```
   127.0.0.1   woo-ecurring.loc
   ```
   
6. Bring up the environment.

    ```
    docker-compose up -d
    ```

Images will be built at this step (if not built yet).
After that you will have the site available at [http://woo-ecurring.loc](http://woo-ecurring.loc) (or at another URL you specified in the `.env` file)

## WP_CLI
Although `WP_CLI` is installed in the `wp_dev` service, it also available as `wp` service for convenience.

To run `WP_CLI` command use `docker-compose run --rm wp ...`.
For example, to show installed plugins:

    ```
    docker-compose run --rm wp plugin list
    ```
