<?php

namespace Tests\Unit\Services;

use App\Services\ModelPhotoImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModelPhotoImageProcessorTest extends TestCase
{
    public function test_processor_generates_normalized_thumbnail_and_watermarked_variants(): void
    {
        $this->assertSame('public/images/watermark-divas-cuyo.png', config('model-photos.watermark.asset'));

        $result = app(ModelPhotoImageProcessor::class)->process(
            UploadedFile::fake()->image('photo.jpg', 3000, 1600),
        );

        $this->assertSame('image/jpeg', $result['mime_type']);
        $this->assertSame('jpg', $result['extension']);
        $this->assertLessThanOrEqual(2000, max($result['processed_width'], $result['processed_height']));
        $this->assertNotSame($result['contents']['processed'], $result['contents']['public']);
        $this->assertNotEmpty($result['contents']['original']);
        $this->assertNotEmpty($result['contents']['thumbnail']);
    }

    public function test_processor_rejects_a_file_with_false_mime_or_invalid_content(): void
    {
        $this->expectException(ValidationException::class);

        app(ModelPhotoImageProcessor::class)->process(
            UploadedFile::fake()->create('fake.jpg', 10, 'image/jpeg'),
        );
    }
}
