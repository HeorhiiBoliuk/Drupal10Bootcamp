<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: opentelemetry/proto/logs/v1/logs.proto

namespace Opentelemetry\Proto\Logs\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * LogsData represents the logs data that can be stored in a persistent storage,
 * OR can be embedded by other protocols that transfer OTLP logs data but do not
 * implement the OTLP protocol.
 * The main difference between this message and collector protocol is that
 * in this message there will not be any "control" or "metadata" specific to
 * OTLP protocol.
 * When new fields are added into this message, the OTLP request MUST be updated
 * as well.
 *
 * Generated from protobuf message <code>opentelemetry.proto.logs.v1.LogsData</code>
 */
class LogsData extends \Google\Protobuf\Internal\Message
{
    /**
     * An array of ResourceLogs.
     * For data coming from a single resource this array will typically contain
     * one element. Intermediary nodes that receive data from multiple origins
     * typically batch the data before forwarding further and in that case this
     * array will contain multiple elements.
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.logs.v1.ResourceLogs resource_logs = 1;</code>
     */
    private $resource_logs;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Opentelemetry\Proto\Logs\V1\ResourceLogs[]|\Google\Protobuf\Internal\RepeatedField $resource_logs
     *           An array of ResourceLogs.
     *           For data coming from a single resource this array will typically contain
     *           one element. Intermediary nodes that receive data from multiple origins
     *           typically batch the data before forwarding further and in that case this
     *           array will contain multiple elements.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Logs\V1\Logs::initOnce();
        parent::__construct($data);
    }

    /**
     * An array of ResourceLogs.
     * For data coming from a single resource this array will typically contain
     * one element. Intermediary nodes that receive data from multiple origins
     * typically batch the data before forwarding further and in that case this
     * array will contain multiple elements.
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.logs.v1.ResourceLogs resource_logs = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getResourceLogs()
    {
        return $this->resource_logs;
    }

    /**
     * An array of ResourceLogs.
     * For data coming from a single resource this array will typically contain
     * one element. Intermediary nodes that receive data from multiple origins
     * typically batch the data before forwarding further and in that case this
     * array will contain multiple elements.
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.logs.v1.ResourceLogs resource_logs = 1;</code>
     * @param \Opentelemetry\Proto\Logs\V1\ResourceLogs[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setResourceLogs($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Opentelemetry\Proto\Logs\V1\ResourceLogs::class);
        $this->resource_logs = $arr;

        return $this;
    }

}

