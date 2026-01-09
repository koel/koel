<?php

namespace App\Http\Controllers;

use App\Models\RadioStation;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamRadioController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(Authenticatable $user, RadioStation $radioStation): StreamedResponse
    {
        $this->authorize('access', $radioStation);

        // Act as a proxy to add CORS headers and avoid CORS issues
        $contentType = 'audio/mpeg'; // Default content type
        
        // Try to get the content type from the radio station URL first
        try {
            $headResponse = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
                ])
                ->head($radioStation->url);
            
            if ($headResponse->successful()) {
                $detectedContentType = $headResponse->header('Content-Type');
                if ($detectedContentType) {
                    $contentType = explode(';', $detectedContentType)[0]; // Remove charset, etc.
                }
            }
        } catch (\Exception $e) {
            // Ignore errors when detecting content type, use default
        }

        return new StreamedResponse(function () use ($radioStation): void {
            try {
                $client = new Client([
                    RequestOptions::TIMEOUT => 0, // No timeout for streaming
                    RequestOptions::VERIFY => false, // Some radio servers have invalid SSL certificates
                    RequestOptions::STREAM => true,
                    RequestOptions::HEADERS => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
                    ],
                ]);

                $response = $client->get($radioStation->url);

                // Forward the Content-Type if available
                $responseContentType = $response->getHeaderLine('Content-Type');
                if ($responseContentType) {
                    header("Content-Type: $responseContentType");
                }

                // Stream the content
                $body = $response->getBody();
                while (!$body->eof()) {
                    echo $body->read(8192); // Read 8KB at a time
                    flush();
                    
                    // Check if client disconnected
                    if (connection_aborted()) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                // If streaming fails, log the error but don't expose it to the client
                logger()->error('Radio stream error', [
                    'station_id' => $radioStation->id,
                    'url' => $radioStation->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Type',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range',
        ]);
    }
}
