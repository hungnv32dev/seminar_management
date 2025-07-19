<?php

namespace App\Services;

use App\Models\Participant;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Generate QR code for a participant's ticket code.
     */
    public function generateQRCode(string $ticketCode, int $size = 200): string
    {
        return QrCode::size($size)
            ->format('png')
            ->generate($ticketCode);
    }

    /**
     * Generate QR code and save to storage.
     */
    public function generateAndSaveQRCode(string $ticketCode, int $size = 200): string
    {
        $qrCode = $this->generateQRCode($ticketCode, $size);
        
        // Create filename
        $filename = 'qrcodes/' . $ticketCode . '.png';
        
        // Save to storage
        Storage::disk('public')->put($filename, $qrCode);
        
        return $filename;
    }

    /**
     * Generate QR code for participant and save to storage.
     */
    public function generateParticipantQRCode(Participant $participant, int $size = 200): string
    {
        return $this->generateAndSaveQRCode($participant->ticket_code, $size);
    }

    /**
     * Get QR code URL for a ticket code.
     */
    public function getQRCodeUrl(string $ticketCode): string
    {
        $filename = 'qrcodes/' . $ticketCode . '.png';
        
        // Check if QR code exists, if not generate it
        if (!Storage::disk('public')->exists($filename)) {
            $this->generateAndSaveQRCode($ticketCode);
        }
        
        return Storage::disk('public')->url($filename);
    }

    /**
     * Get QR code URL for participant.
     */
    public function getParticipantQRCodeUrl(Participant $participant): string
    {
        return $this->getQRCodeUrl($participant->ticket_code);
    }

    /**
     * Generate QR code as base64 string.
     */
    public function generateQRCodeBase64(string $ticketCode, int $size = 200): string
    {
        $qrCode = $this->generateQRCode($ticketCode, $size);
        return base64_encode($qrCode);
    }

    /**
     * Generate QR code data URI for embedding in emails.
     */
    public function generateQRCodeDataUri(string $ticketCode, int $size = 200): string
    {
        $base64 = $this->generateQRCodeBase64($ticketCode, $size);
        return 'data:image/png;base64,' . $base64;
    }

    /**
     * Validate and decode QR code content.
     */
    public function validateTicketCode(string $ticketCode): ?Participant
    {
        return Participant::where('ticket_code', $ticketCode)
            ->with(['workshop', 'ticketType'])
            ->first();
    }

    /**
     * Generate QR code with custom styling.
     */
    public function generateStyledQRCode(string $ticketCode, array $options = []): string
    {
        $qrCode = QrCode::size($options['size'] ?? 200)
            ->format($options['format'] ?? 'png')
            ->margin($options['margin'] ?? 2);

        // Add color if specified
        if (isset($options['color'])) {
            $qrCode->color($options['color']['r'], $options['color']['g'], $options['color']['b']);
        }

        // Add background color if specified
        if (isset($options['backgroundColor'])) {
            $bg = $options['backgroundColor'];
            $qrCode->backgroundColor($bg['r'], $bg['g'], $bg['b']);
        }

        // Add logo if specified
        if (isset($options['logo'])) {
            $qrCode->merge($options['logo'], 0.3, true);
        }

        return $qrCode->generate($ticketCode);
    }

    /**
     * Generate QR code with workshop branding.
     */
    public function generateBrandedQRCode(Participant $participant, array $options = []): string
    {
        $defaultOptions = [
            'size' => 250,
            'format' => 'png',
            'margin' => 2,
            'color' => ['r' => 0, 'g' => 0, 'b' => 0],
            'backgroundColor' => ['r' => 255, 'g' => 255, 'b' => 255],
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->generateStyledQRCode($participant->ticket_code, $options);
    }

    /**
     * Generate QR code with participant information embedded.
     */
    public function generateDetailedQRCode(Participant $participant): string
    {
        // Create JSON data with participant info
        $data = [
            'ticket_code' => $participant->ticket_code,
            'participant_name' => $participant->name,
            'workshop_name' => $participant->workshop->name,
            'workshop_date' => $participant->workshop->date_time->format('Y-m-d H:i'),
            'ticket_type' => $participant->ticketType->name,
        ];

        $jsonData = json_encode($data);

        return QrCode::size(300)
            ->format('png')
            ->margin(2)
            ->generate($jsonData);
    }

    /**
     * Bulk generate QR codes for multiple participants.
     */
    public function bulkGenerateQRCodes(array $participantIds): array
    {
        $participants = Participant::whereIn('id', $participantIds)
            ->with(['workshop', 'ticketType'])
            ->get();

        $results = [];

        foreach ($participants as $participant) {
            try {
                $filename = $this->generateParticipantQRCode($participant);
                $results[$participant->id] = [
                    'success' => true,
                    'filename' => $filename,
                    'url' => $this->getParticipantQRCodeUrl($participant),
                ];
            } catch (\Exception $e) {
                $results[$participant->id] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Clean up old QR code files.
     */
    public function cleanupOldQRCodes(int $daysOld = 30): int
    {
        $files = Storage::disk('public')->files('qrcodes');
        $deletedCount = 0;
        $cutoffDate = now()->subDays($daysOld);

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            
            if ($lastModified < $cutoffDate->timestamp) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get QR code statistics.
     */
    public function getQRCodeStatistics(): array
    {
        $qrCodePath = 'qrcodes';
        $files = Storage::disk('public')->files($qrCodePath);

        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += Storage::disk('public')->size($file);
        }

        return [
            'total_files' => count($files),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'storage_path' => Storage::disk('public')->path($qrCodePath),
        ];
    }

    /**
     * Generate QR code for check-in verification.
     */
    public function generateCheckInQRCode(Participant $participant): string
    {
        // Create check-in specific data
        $checkInData = [
            'action' => 'checkin',
            'ticket_code' => $participant->ticket_code,
            'workshop_id' => $participant->workshop_id,
            'participant_id' => $participant->id,
            'timestamp' => now()->timestamp,
        ];

        return QrCode::size(200)
            ->format('png')
            ->generate(json_encode($checkInData));
    }

    /**
     * Verify check-in QR code data.
     */
    public function verifyCheckInQRCode(string $qrData): array
    {
        try {
            $data = json_decode($qrData, true);

            if (!$data || !isset($data['action']) || $data['action'] !== 'checkin') {
                return ['valid' => false, 'error' => 'Invalid QR code format'];
            }

            $participant = Participant::where('ticket_code', $data['ticket_code'])
                ->where('id', $data['participant_id'])
                ->where('workshop_id', $data['workshop_id'])
                ->with(['workshop', 'ticketType'])
                ->first();

            if (!$participant) {
                return ['valid' => false, 'error' => 'Participant not found'];
            }

            return [
                'valid' => true,
                'participant' => $participant,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Invalid QR code data'];
        }
    }
}