# Test for candidates

This repository contains an implementation of the problem proposed in the [Test for candidates repository](https://github.com/systemeio/test-for-candidates).

The stack used for this implementation is:
**nginx 1.25** + **PHP 8.2** + **Postgres 14.9** + **Symfony 6.4**.

The implementation is just for testing purposes. It is not optimized nor intended to be used in PRODUCTION environments.

## Prerequisites

In order to build and run the code, your environment must have installed:

- Git
- Docker
- Docker compose

## Building the infrastructure

To make things easier, a Makefile is provided with the code, so you can run the following command for start the building process

```
make docker-build
```

## Running the App

Once the building process finishes, you can run the App using the following command:

```
make docker-up
```

If you want to use xDebug (installed in the Docker image), you can start the app using the command:

```
docker-up-with-xdebug
```

You may feel that things are slow down because the overhead added by xDebug.

Then, you have to install all the composer packages required by the application:

```
make composer install
```

Next, you have to run the migrations:

```
make symfony doctrine:migrations:migrate
```

Finally, load the fixtures so you have data to play with:

```
make symfony doctrine:fixtures:load
```

Once the environment is up and running, all the endpoints will be accessed using the host:

```
http://localhost:8000
```

If you want see the whole set of commands available via the Makefile, you can run:

```
make help
```

## Using the endpoints

The application defines 2 endpoints:

### Calculate the price (```POST /calculate-price```)

You can test the endpoint using curl:

```
curl -X POST -H "Content-Type: application/json" -d '{"product": 10,"taxNumber": "FRXY0123456789","couponCode": "P50"}' localhost:8000/calculate-price
```

If all request data are valid, the response of the request should be something like this:

```
{"data": { "price":60 }}
```

If there is an invalid request data, the endpoint will return the HTTP code 400 and as response a description about the detected problem:

```
// Here, the taxCode does not follow the expected format
curl -X POST -H "Content-Type: application/json" -d '{"product": 10,"taxNumber": "FR0123456789","couponCode": "P50"}' localhost:8000/calculate-price
```
The response would be:

```
{"errors": [{"propertyPath": "taxCode", "message":" The code has an invalid format"}]}
```

### Complete a purchase (```POST /purchase```)

You can test the endpoint using curl:

```
curl -X POST -H "Content-Type: application/json" -d '{"product": 10,"taxNumber": "FRXY0123456789","couponCode": "P50", "paymentProcessor": "paypal"}' localhost:8000/purchase
```

If all request data are valid, the response of the request should be something like this:

```
// The values "true" or "false" are irrelevant for this implementation.
// If a "boolean" value is returned, the request is considered executed

{"data": {"payment_processed": true }
```

If there is an invalid request data, the endpoint will return the HTTP code 400 and as response a description about the detected problem:

```
// Here, the payment processor is not specified.
curl -X POST -H "Content-Type: applicatioXY0123456789","couponCode": "P50"}' localhost:8000/purchase
```
The response would be:

```
{"errors":[{"propertyPath":"paymentProcessor","message":"This value should not be blank."}]}
```
There are some additional validations on several fields. For instance, the only payment processors supported are "paypal", "stripe" and "redsys" (this one was added to show how easy is adding new payment processors). So, if an "unregistered" processor is provided:

```
curl -X POST -H "Content-Type: application/json" -d '{"product": 10,"taxNumber": "FRXY0123456789","couponCode": "P50", "paymentProcessor": "datatrans"}' localhost:8000/purchase
```

You will have the following response:

```
{"error":"Payment processor not supported"}
```

For both request, you are free to read the code so you can see all about the implemented validations and how the requests handle these situations.

### Running tests

Before running the tests included, it is important that you complete the following tasks:

- Add a connection to the database on Test environment. For this, add, in the root of the project, a file named ```.env.test.local``` with the following content:

```
###> doctrine/doctrine-bundle ###
DATABASE_URL="postgresql://root:passwd@tfc_db:5432/tfc?serverVersion=16&charset=utf8"
###< doctrine/doctrine-bundle ###
```
- Create a database for testing:

```
APP_ENV=test make symfony doctrine:database:create
```
A database named ```tfc_test``` will be created.

- Run the migrations:

```
APP_ENV=test make symfony doctrine:migrations:migrate
```

- Then, load the fixtures required:

```
APP_ENV=test make symfony doctrine:fixtures:load
```

- Run the tests:

```
make tests
```

Enjoy the App!

### Final thoughts

The application was developed keeping the Symfony structure untouched. I added a couple of folder (e.g. "Services") to have the possibility to "decouple" code from Controllers and allow injecting some classes.

The application implements some nice features like:

- A custom validator for validate the format of the Tax codes. The implemented way allow to add new templates without any modification of the implemented logic
```
class TaxCodeFormatValidator extends ConstraintValidator
{
    // You can add new templates to this array
    final public const TAX_CODE_TEMPLATES = [
        'DE' => 'DEXXXXXXXXX',
        'IT' => 'ITXXXXXXXXXXX',
        'GR' => 'GRXXXXXXXXX',
        'FR' => 'FRYYXXXXXXXXXX'
    ];
    
    // the rest of the logic keeps untouched
    ...
}
```
- The interface ```PaymentProcessorInterface``` was added as a way to having a common signature for the method intended to be called to perform payments. So, although the payment processor requested to be included via composer package (https://github.com/systemeio/test-for-candidates) were added to the application, a new processor (i.e. Redsys) was added to show the way to do that:

```
class RedsysPaymentProcessor implements PaymentProcessorInterface
{
    public function pay($price): bool
    {
        return false; // Let's suppose the payment platform is currently denying any purchase :S
    }
}
```
And, that's it! ... I hope you like what I've done. If so, please leave a comment. If you think I could do the things in a different (and easier) way, please leave a comment. I would appreciate it.

Thanks so much!,

Cobis
