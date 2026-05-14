<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait HasCustomShortflakePrimary
 *
 * Provides functionality to generate, parse, and work with unique IDs inspired by Twitter's Snowflake IDs.
 * This trait offers methods for creating both full-length and shortened Snowflake IDs, ensuring uniqueness
 * even under concurrent requests. It also includes utilities for parsing Snowflake IDs into their constituent components.
 */
trait HasCustomShortflakePrimary
{
    // Replace constants with private static variables
    /** @var string Default epoch datetime */
    private static $DEFAULT_EPOCH_DATETIME = '2023-08-28 00:00:00';

    /** @var int Total bits of the ID */
    private static $ID_BITS = 30;

    /** @var int Bits for timestamp - reduced for shorter IDs */
    private static $TIMESTAMP_BITS = 21;

    /** @var int Bits for worker ID - disabled for shorter IDs */
    private static $WORKER_ID_BITS = 0;

    /** @var int Bits for datacenter ID - disabled for shorter IDs */
    private static $DATACENTER_ID_BITS = 0;

    /** @var int Bits for sequence - reduced for shorter IDs */
    private static $SEQUENCE_BITS = 9;

    /** @var int Timeout in milliseconds */
    private static $TIMEOUT = 1000;

    /** @var int Maximum sequence value */
    private static $MAX_SEQUENCE = 511; // 2^9 - 1

    // Properties from the Snowflake class
    /** @var string Epoch timestamp in milliseconds */
    protected static $epoch;

    /** @var string Last timestamp when an ID was generated */
    private static $lastTimestamp;

    /** @var int Sequence number */
    private static $sequence = 0;

    /** @var int Datacenter ID */
    private static $datacenterId = 0;

    /** @var int Worker ID */
    private static $workerId = 0;

    /**
     * Initialize Snowflake properties from configuration.
     */
    public static function initializeSnowflakeProperties()
    {
        $epochDatetime = config('snowflake.epoch', self::$DEFAULT_EPOCH_DATETIME);
        $timestamp = strtotime($epochDatetime);
        self::$epoch = bcmul((string) $timestamp, '1000');
        self::$workerId = 0; // Disabled
        self::$datacenterId = 0; // Disabled
        self::$lastTimestamp = self::$epoch;
    }

    /**
     * Boot the trait.
     *
     * This method is automatically called by Laravel when the model is being booted.
     * It sets up the model's events and initializes the Snowflake properties.
     */
    public static function bootHasCustomShortflakePrimary()
    {
        if (config('snowflake.use_snowflake', true)) {
            static::creating(function ($model) {
                $model->{$model->getKeyName()} = $model->short();
            });
        }

        self::initializeSnowflakeProperties();
    }

    /**
     * Make a sequence ID for the current timestamp.
     *
     * @param  string  $currentTime  Current timestamp in milliseconds.
     * @return int Sequence ID.
     */
    public function makeSequenceId(string $currentTime): int
    {
        // Use a lock to handle concurrency
        DB::transaction(function () use (&$currentTime) {
            if (bccomp(self::$lastTimestamp, $currentTime) === 0) {
                self::$sequence = (self::$sequence + 1) & self::$MAX_SEQUENCE;
                if (self::$sequence == 0) {
                    // Sequence overflow, wait for the next millisecond
                    do {
                        $currentTime = $this->timestamp();
                    } while (bccomp($currentTime, self::$lastTimestamp) <= 0);
                }
            } else {
                self::$sequence = 0;
            }
        });

        self::$lastTimestamp = $currentTime;

        return self::$sequence;
    }

    /**
     * Generate a new Snowflake ID.
     *
     * @return string Snowflake ID.
     *
     * @throws \Exception If the clock moves backward.
     */
    public function generateId(): string
    {
        $currentTime = $this->timestamp();
        if (bccomp($currentTime, self::$lastTimestamp) < 0) {
            // Clock moved backward, throw an exception
            throw new \Exception('Clock moved backward');
        }

        $sequenceId = $this->makeSequenceId($currentTime);

        return $this->toSnowflakeId($currentTime, $sequenceId);
    }

    /**
     * Generate the next Snowflake ID.
     *
     * @return string Next Snowflake ID.
     */
    public function next(): string
    {
        return $this->generateId();
    }

    /**
     * Generate a short Snowflake ID with retry mechanism.
     *
     * @return string Short Snowflake ID.
     *
     * @throws \Exception If the clock moves backward after retries.
     */
    public function short(): string
    {
        $retryCount = 0;
        $maxRetries = 5; // Adjust this based on your needs

        while ($retryCount < $maxRetries) {
            $currentTime = $this->timestamp();

            if (bccomp($currentTime, self::$lastTimestamp) >= 0) {
                $sequenceId = $this->makeSequenceId($currentTime);

                return $this->toShortflakeId($currentTime, $sequenceId);
            }

            // Log the timestamps for debugging
            // \Log::warning("Clock moved backward. Current Time: {$currentTime} Last Timestamp: " . self::$lastTimestamp);

            $retryCount++;
            usleep(10); // Sleep for 10 microseconds before retrying
        }

        throw new \Exception('Clock moved backward after retries.');
    }

    /**
     * Convert timestamp and sequence number to a short Snowflake ID.
     *
     * @param  string  $currentTime  Current timestamp in milliseconds since the epoch.
     * @param  int  $sequenceId  Sequence number.
     * @return string Short Snowflake ID.
     */
    public function toShortflakeId(string $currentTime, int $sequenceId): string
    {
        $diff = bcsub($currentTime, self::$epoch);
        // Mask to fit timestamp bits
        $maskedDiff = bcmod($diff, bcpow('2', (string) self::$TIMESTAMP_BITS));
        $shifted = bcmul($maskedDiff, bcpow('2', (string) self::$SEQUENCE_BITS));

        return bcadd($shifted, (string) $sequenceId);
    }

    /**
     * Convert timestamp, datacenter ID, worker ID, and sequence number to a Snowflake ID.
     *
     * @param  string  $currentTime  Current timestamp in milliseconds since the epoch.
     * @param  int  $sequenceId  Sequence number.
     * @return string Snowflake ID.
     */
    public function toSnowflakeId(string $currentTime, int $sequenceId): string
    {
        $workerIdLeftShift = self::$SEQUENCE_BITS;
        $datacenterIdLeftShift = self::$WORKER_ID_BITS + self::$SEQUENCE_BITS;
        $timestampLeftShift = self::$DATACENTER_ID_BITS + self::$WORKER_ID_BITS + self::$SEQUENCE_BITS;

        $diff = bcsub($currentTime, self::$epoch);
        // Mask to fit timestamp bits
        $maskedDiff = bcmod($diff, bcpow('2', (string) self::$TIMESTAMP_BITS));

        $tsShifted = bcmul($maskedDiff, bcpow('2', (string) $timestampLeftShift));
        $dcShifted = bcmul((string) self::$datacenterId, bcpow('2', (string) $datacenterIdLeftShift));
        $workerShifted = bcmul((string) self::$workerId, bcpow('2', (string) $workerIdLeftShift));
        $seq = (string) $sequenceId;

        $id = bcadd($tsShifted, $dcShifted);
        $id = bcadd($id, $workerShifted);
        $id = bcadd($id, $seq);

        return $id;
    }

    /**
     * Get the current timestamp in milliseconds.
     *
     * @return string Current timestamp in milliseconds.
     */
    public function timestamp(): string
    {
        $micro = microtime(true);
        $ms = floor($micro * 1000);

        return number_format($ms, 0, '.', '');
    }

    /**
     * Parse a Snowflake ID into its components.
     *
     * @param  int  $id  Snowflake ID.
     * @return array Parsed components of the Snowflake ID.
     */
    public function parse(int $id): array
    {
        $id = decbin($id);

        $datacenterIdLeftShift = self::$WORKER_ID_BITS + self::$SEQUENCE_BITS;
        $timestampLeftShift = self::$DATACENTER_ID_BITS + self::$WORKER_ID_BITS + self::$SEQUENCE_BITS;

        $binaryTimestamp = substr($id, 0, -$timestampLeftShift);
        $binarySequence = substr($id, -self::$SEQUENCE_BITS);
        $binaryWorkerId = substr($id, -$datacenterIdLeftShift, self::$WORKER_ID_BITS);
        $binaryDatacenterId = substr($id, -$timestampLeftShift, self::$DATACENTER_ID_BITS);
        $timestamp = bindec($binaryTimestamp);
        $datetime = date('Y-m-d H:i:s', ((int) (($timestamp + (int) bcdiv(self::$epoch, '1000'))) | 0));

        return [
            'binary_length' => strlen($id),
            'binary' => $id,
            'binary_timestamp' => $binaryTimestamp,
            'binary_sequence' => $binarySequence,
            'binary_worker_id' => $binaryWorkerId,
            'binary_datacenter_id' => $binaryDatacenterId,
            'timestamp' => $timestamp,
            'sequence' => bindec($binarySequence),
            'worker_id' => bindec($binaryWorkerId),
            'datacenter_id' => bindec($binaryDatacenterId),
            'epoch' => self::$epoch,
            'datetime' => $datetime,
        ];
    }

    /**
     * Get the incrementing status of the model's primary key.
     *
     * @return bool False, because the primary key is not auto-incrementing.
     */
    public function getIncrementing()
    {
        return (config('snowflake.use_snowflake', true)) ? false : true;
    }

    /**
     * Get the data type of the model's primary key.
     *
     * @return string Data type of the primary key.
     */
    public function getKeyType()
    {
        return 'string';
    }
}
