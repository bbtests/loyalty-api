# Payment Service Architecture

This document describes the payment service architecture that has been optimized for scalability and maintainability.

## Architecture Overview

The payment system implements a streamlined architecture with integrated provider management, proper dependency injection, and asynchronous job processing for cashback payments.

### Key Components

1. **PaymentService** - Main service class with integrated provider management
2. **PaymentProviderInterface** - Contract for payment providers
3. **BasePaymentProvider** - Abstract base class for provider implementations
4. **Provider Implementations** - PaystackProvider, FlutterwaveProvider
5. **ProcessCashbackPayment** - Job for asynchronous cashback processing
6. **PaymentController** - RESTful API endpoints for payment operations
7. **Service Container Bindings** - Laravel service container integration

## Service Container Bindings

The payment service is automatically bound to the Laravel service container through constructor injection. The service is registered as a singleton and can be accessed through dependency injection or the service container.

### Automatic Registration

The PaymentService is automatically available through Laravel's service container:

```php
// Constructor injection (recommended)
public function __construct(private PaymentService $paymentService) {}

// Service container access
$paymentService = app(PaymentService::class);
```

### Service Provider Registration

If needed, the service can be explicitly bound in `AppServiceProvider`:

```php
// In AppServiceProvider
$this->app->singleton(PaymentService::class);
$this->app->alias(PaymentService::class, 'payment');
```

## Usage Patterns

### 1. Direct Service Injection (Recommended)

```php
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}
    
    public function initializePayment(Request $request)
    {
        $user = auth()->user();
        $amount = $request->input('amount');
        $provider = $request->input('provider');
        
        $reference = 'pay_' . time() . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8);
        
        return $this->paymentService->initializePayment($user, $amount, $reference, $provider);
    }
}
```

### 2. Using app() Helper

```php
class PaymentController extends Controller
{
    public function getConfiguration()
    {
        $paymentService = app(PaymentService::class);
        
        return $paymentService->getConfiguration();
    }
}
```

### 3. Provider-Specific Operations

```php
// Get specific provider
$provider = $this->paymentService->getProvider('paystack');

// Check provider availability
if ($this->paymentService->isProviderAvailable('paystack')) {
    $result = $this->paymentService->initializePayment($user, $amount, $reference, 'paystack');
}

// Get provider information
$providerInfo = $this->paymentService->getProviderInfo('paystack');
```

### 4. Configuration Access

```php
// Get full configuration
$config = $this->paymentService->getConfiguration();

// Get supported currencies
$currencies = $this->paymentService->getSupportedCurrencies();

// Get amount limits
$minAmount = $this->paymentService->getMinimumAmount();
$maxAmount = $this->paymentService->getMaximumAmount();
```

## Integrated Provider Management

### Provider Architecture

The payment system uses a factory pattern with provider interfaces:

```php
// PaymentProviderInterface contract
interface PaymentProviderInterface
{
    public function initializePayment(User $user, float $amount, string $reference): array;
    public function verifyPayment(string $reference): array;
    public function processCashback(User $user, float $amount): array;
    public function isAvailable(): bool;
    public function getName(): string;
    public function getSupportedCurrencies(): array;
    public function getMinimumAmount(): float;
    public function getMaximumAmount(): float;
}

// BasePaymentProvider abstract class
abstract class BasePaymentProvider implements PaymentProviderInterface
{
    protected array $config;
    protected string $providerName;
    
    // Common functionality for all providers
    protected function validatePaymentData(User $user, float $amount): ?string;
    protected function generateReference(string $prefix = 'pay'): string;
    protected function logPayment(string $action, array $data, ?string $error = null): void;
    protected function createResponse(bool $success, array $data = [], ?string $error = null): array;
}
```

### Dynamic Provider Management

```php
use App\Services\PaymentService;

// Register a new provider dynamically
$paymentService = app(PaymentService::class);
$paymentService->registerProvider('custom', CustomPaymentProvider::class);

// Unregister when no longer needed
$paymentService->unregisterProvider('custom');
```

### Provider Availability Checking

```php
// Check if provider is available
if ($paymentService->isProviderAvailable('paystack')) {
    $provider = $paymentService->getProvider('paystack');
    // Use provider
}

// Get all available providers
$availableProviders = $paymentService->getAvailableProviders();

// Get provider information
$providerInfo = $paymentService->getProviderInfo('paystack');
$allProvidersInfo = $paymentService->getAllProvidersInfo();
```

## Fallback Mechanisms

### Payment with Fallback

```php
$preferredProviders = ['paystack', 'flutterwave'];
$result = $paymentService->processPaymentWithFallback($user, $amount, $reference, $preferredProviders);
```

### Cashback with Fallback

```php
$preferredProviders = ['paystack', 'flutterwave'];
$result = $paymentService->processCashbackWithFallback($user, $amount, $preferredProviders);
```

## Error Handling

### Comprehensive Error Handling

```php
try {
    $result = $paymentService->initializePayment($user, $amount, $reference, 'paystack');
} catch (\InvalidArgumentException $e) {
    // Provider not found
    return response()->json(['error' => 'Provider not found'], 400);
} catch (\RuntimeException $e) {
    // Provider not available
    return response()->json(['error' => 'Provider not available'], 400);
} catch (\Exception $e) {
    // Other errors
    return response()->json(['error' => 'Payment failed'], 500);
}
```

## Configuration

### Environment Variables

```env
# Default payment provider
PAYMENT_PROVIDER=paystack

# Paystack configuration
PAYSTACK_ENABLED=true
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_SECRET_KEY=sk_test_...
PAYSTACK_PAYMENT_URL=https://api.paystack.co

# Flutterwave configuration
FLUTTERWAVE_ENABLED=false
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_TEST-...
FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST-...
FLUTTERWAVE_BASE_URL=https://api.flutterwave.com/v3

# Payment limits
PAYMENT_MIN_AMOUNT=1.0
PAYMENT_MAX_AMOUNT=1000000.0

# Payment settings
PAYMENT_DEFAULT_CURRENCY=NGN
PAYMENT_CALLBACK_URL=/payment/callback
PAYMENT_WEBHOOK_URL=/payment/webhook
```

### Configuration File Structure

The payment configuration is defined in `config/payment.php`:

```php
return [
    'default_provider' => env('PAYMENT_PROVIDER', 'paystack'),
    
    'providers' => [
        'paystack' => [
            'enabled' => env('PAYSTACK_ENABLED', false),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'base_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
        ],
        'flutterwave' => [
            'enabled' => env('FLUTTERWAVE_ENABLED', false),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        ],
    ],
    
    'limits' => [
        'minimum_amount' => env('PAYMENT_MIN_AMOUNT', 1.0),
        'maximum_amount' => env('PAYMENT_MAX_AMOUNT', 1000000.0),
    ],
    
    'settings' => [
        'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'NGN'),
        'supported_currencies' => ['NGN', 'USD', 'GBP', 'EUR'],
        'callback_url' => env('PAYMENT_CALLBACK_URL', '/payment/callback'),
        'webhook_url' => env('PAYMENT_WEBHOOK_URL', '/payment/webhook'),
    ],
];
```

### Configuration Access

```php
// Get full configuration
$config = $paymentService->getConfiguration();

// Get provider-specific info
$paystackInfo = $paymentService->getProviderInfo('paystack');

// Get all providers info
$allProviders = $paymentService->getAllProvidersInfo();
```

## API Endpoints

The payment system exposes the following RESTful endpoints through the PaymentController:

### Get Payment Configuration
```
GET /api/v1/payments/configuration
```
Returns complete payment configuration including providers, currencies, and limits.

### Get Available Providers
```
GET /api/v1/payments/providers
```
Returns information about all registered payment providers and their availability.

### Get Public Key
```
GET /api/v1/payments/public-key?provider=paystack
```
Returns the public key for the specified provider (required for frontend integration).

### Initialize Payment
```
POST /api/v1/payments/initialize
{
    "amount": 1000,
    "provider": "paystack",
    "description": "Payment for loyalty program"
}
```
Initializes a payment transaction and returns authorization URL for completion.

### Verify Payment
```
POST /api/v1/payments/verify
{
    "reference": "pay_1234567890_abc123",
    "provider": "paystack"
}
```
Verifies a completed payment transaction and returns transaction details.

### Process Cashback
```
POST /api/v1/payments/cashback
{
    "amount": 500,
    "provider": "paystack",
    "description": "Loyalty cashback"
}
```
Processes cashback payment to user's account through the specified provider.

### Webhook Endpoint
```
POST /api/v1/webhooks/payment
```
Handles payment webhooks from external providers for transaction status updates.

## Asynchronous Job Processing

### ProcessCashbackPayment Job

The system uses Laravel's job queue for processing cashback payments asynchronously:

```php
use App\Jobs\ProcessCashbackPayment;

// Dispatch cashback payment job
ProcessCashbackPayment::dispatch($user, $amount, $transactionId);
```

### Job Configuration

```php
class ProcessCashbackPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $backoff = 60;
    
    public function handle(PaymentService $paymentService): void
    {
        // Create cashback payment record
        $cashbackPayment = CashbackPayment::create([
            'user_id' => $this->user->id,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'payment_provider' => config('payment.default_provider', 'paystack'),
            'status' => 'pending',
        ]);
        
        // Process payment through provider
        $result = $paymentService->processCashback($this->user, $this->amount);
        
        // Update payment record with result
        $cashbackPayment->update([
            'provider_transaction_id' => $result['transaction_id'] ?? null,
            'status' => $result['status'] ?? 'failed',
            'payment_details' => $result,
        ]);
    }
}
```

### Queue Configuration

```env
# Queue configuration
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
```

## Webhook Handling

### Payment Webhook Processing

The system handles webhooks from payment providers to update transaction statuses:

```php
// Webhook endpoint
POST /api/v1/webhooks/payment

// Webhook processing in TransactionController
public function handlePaymentWebhook(Request $request)
{
    // Verify webhook signature
    // Process payment status update
    // Update transaction record
    // Trigger related events
}
```

### Webhook Security

- Signature verification for webhook authenticity
- IP whitelisting for known provider IPs
- Rate limiting to prevent abuse
- Logging for audit trails

## Scalability Features

### 1. Integrated Provider Management
- Dynamic provider registration/unregistration
- Lazy loading of providers
- Easy addition of new providers

### 2. Service Container Integration
- Singleton pattern for performance
- Proper dependency injection
- Multiple access patterns (service, alias)

### 3. Fallback Mechanisms
- Automatic provider fallback
- Configurable preferred providers
- Graceful error handling

### 4. Configuration Management
- Environment-based configuration
- Runtime configuration access
- Provider-specific settings

### 5. Error Handling
- Comprehensive exception handling
- Detailed error logging
- Graceful degradation

## Performance Optimizations

### 1. Singleton Pattern
- Single instance per request
- Reduced memory usage
- Consistent state

### 2. Lazy Loading
- Providers created only when needed
- Factory pattern for on-demand creation
- Reduced initialization overhead

### 3. Caching
- Provider availability caching
- Configuration caching
- Reduced repeated lookups

## Testing

### Provider Testing

```php
// Test with specific provider
$result = $paymentService->initializePayment($user, $amount, $reference, 'paystack');

// Test provider availability
if ($paymentService->isProviderAvailable('flutterwave')) {
    $provider = $paymentService->getProvider('flutterwave');
}
```

## Architecture Benefits

### Key Benefits

1. **Simplified Architecture** - Clean factory-based provider management
2. **Better Performance** - Singleton pattern and lazy loading
3. **Easier Testing** - Simplified dependency injection
4. **More Flexible** - Factory pattern for dynamic provider management
5. **Better Scalability** - Multiple access patterns and fallback mechanisms

### Backward Compatibility

The restructured system maintains backward compatibility:

```php
// Old way (still works)
$result = $paymentService->processCashback($user, $amount);

// New way (recommended)
$result = $paymentService->processCashback($user, $amount, 'paystack');
```

## Best Practices

### 1. Use Direct Injection
```php
public function __construct(private PaymentService $paymentService) {}
```

### 2. Implement Fallback Mechanisms
```php
$preferredProviders = ['paystack', 'flutterwave'];
$result = $paymentService->processPaymentWithFallback($user, $amount, $reference, $preferredProviders);
```

### 3. Handle Errors Gracefully
```php
try {
    $result = $paymentService->initializePayment($user, $amount, $reference);
} catch (\InvalidArgumentException $e) {
    Log::error('Provider not found', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Provider not found'], 400);
} catch (\Exception $e) {
    Log::error('Payment failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Payment failed'], 500);
}
```

### 4. Use Configuration Management
```php
$config = $paymentService->getConfiguration();
$availableProviders = $config['providers'];
```

### 5. Validate Payment Data
```php
// Provider-level validation
$validationError = $this->validatePaymentData($user, $amount);
if ($validationError) {
    return $this->createResponse(false, [], $validationError);
}
```

### 6. Use Asynchronous Processing
```php
// For cashback payments
ProcessCashbackPayment::dispatch($user, $amount, $transactionId);
```

### 7. Implement Proper Logging
```php
$this->logPayment('initialize_payment', [
    'user_id' => $user->id,
    'amount' => $amount,
    'reference' => $reference,
]);
```

### 8. Test with Multiple Providers
```php
// Test provider availability
if ($paymentService->isProviderAvailable('paystack')) {
    $result = $paymentService->initializePayment($user, $amount, $reference, 'paystack');
}
```

### 9. Use Webhook Security
```php
// Verify webhook signatures
// Implement IP whitelisting
// Use rate limiting
```

### 10. Monitor Payment Performance
```php
// Track payment processing times
// Monitor success/failure rates
// Set up alerts for failures
```

This architecture provides comprehensive payment processing capabilities with:

- **Scalability**: Factory pattern with dynamic provider management
- **Reliability**: Fallback mechanisms and comprehensive error handling  
- **Performance**: Singleton pattern, lazy loading, and asynchronous processing
- **Maintainability**: Clean interfaces, dependency injection, and standardized responses
- **Security**: Webhook verification, input validation, and audit logging
- **Flexibility**: Multiple provider support with easy extensibility
- **Monitoring**: Comprehensive logging and error tracking

The system maintains backward compatibility while providing modern payment processing features for the Bumpa loyalty platform.
