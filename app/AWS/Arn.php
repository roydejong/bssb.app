<?php

namespace app\AWS;

class Arn
{
    /**
     * The partition in which the resource is located. A partition is a group of AWS Regions. Each AWS account is scoped
     * to one partition. The following are the supported partitions:
     * - aws - AWS Regions
     * - aws-cn - China Regions
     * - aws-us-gov - AWS GovCloud (US) Regions
     */
    public ?string $awsPartition = null;

    /**
     * The service namespace that identifies the AWS product.
     * Also known as service prefix.
     *
     * For example, s3 for Amazon S3.
     */
    public ?string $awsService = null;

    /**
     * The Region code.
     *
     * For example, us-east-2 for US East (Ohio).
     * @see https://docs.aws.amazon.com/general/latest/gr/rande.html#regional-endpoints
     */
    public ?string $awsRegion;

    /**
     * The ID of the AWS account that owns the resource, without the hyphens. For example, 123456789012.
     */
    public ?string $awsAccountId = null;

    /**
     * The resource identifier. This part of the ARN can be the name or ID of the resource or a resource path.
     * Some resource identifiers include a parent resource.
     *
     * For example, user/Bob for an IAM user or instance/i-1234567890abcdef0 for an EC2 instance.
     */
    public ?string $awsResourceId = null;
}