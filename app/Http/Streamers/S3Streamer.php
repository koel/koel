<?php

namespace App\Http\Streamers;

use App\Models\Song;
use AWS;
use Aws\AwsClient;

class S3Streamer extends Streamer implements StreamerInterface
{
    public function __construct(Song $song)
    {
        parent::__construct($song);
    }

    /**
     * Stream the current song through S3.
     * Actually, we only redirect to the S3 object's location.
     *
     * @param AwsClient $s3
     *
     * @return string
     */
    public function stream(AwsClient $s3 = null)
    {
        if (!$s3) {
            $s3 = AWS::createClient('s3');
        }

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $this->song->s3_params['bucket'],
            'Key' => $this->song->s3_params['key'],
        ]);

        $request = $s3->createPresignedRequest($cmd, '+1 hour');

        // Get and redirect to the actual presigned-url
        return redirect((string) $request->getUri());
    }
}
