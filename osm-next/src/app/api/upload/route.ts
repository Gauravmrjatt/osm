import { NextRequest, NextResponse } from 'next/server';
import { writeFile, mkdir } from 'fs/promises';
import path from 'path';
import { uploadVideoToCloudinary } from '@/lib/cloudinary';

export async function POST(request: NextRequest) {
  try {
    const formData = await request.formData();
    
    const logoImage = formData.get('logo_image') as File | null;
    const videoFile = formData.get('video_file') as File | null;
    const bannerImage = formData.get('banner_image') as File | null;
    
    const uploadDir = path.join(process.cwd(), 'public', 'uploads');
    
    try {
      await mkdir(uploadDir, { recursive: true });
    } catch {
      // Directory might already exist
    }
    
    if (logoImage || bannerImage) {
      const file = (logoImage || bannerImage)!;
      const bytes = await file.arrayBuffer();
      const buffer = Buffer.from(bytes);
      
      const ext = file.name.split('.').pop()?.toLowerCase();
      const allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
      
      if (!ext || !allowedImages.includes(ext)) {
        return NextResponse.json({ success: false, error: 'Invalid image format' }, { status: 400 });
      }
      
      const filename = `img_${Date.now()}_${Math.random().toString(36).substring(7)}.${ext}`;
      const filepath = path.join(uploadDir, filename);
      
      await writeFile(filepath, buffer);
      
      return NextResponse.json({ 
        success: true, 
        filename, 
        type: 'image',
        source: 'local'
      });
    }
    
    if (videoFile) {
      const bytes = await videoFile.arrayBuffer();
      const buffer = Buffer.from(bytes);
      
      // Check file size (max 50MB)
      if (buffer.length > 50 * 1024 * 1024) {
        return NextResponse.json({ success: false, error: 'Video size must be less than 50MB' }, { status: 400 });
      }
      
      const ext = videoFile.name.split('.').pop()?.toLowerCase();
      const allowedVideos = ['mp4', 'webm'];
      
      if (!ext || !allowedVideos.includes(ext)) {
        return NextResponse.json({ success: false, error: 'Invalid video format' }, { status: 400 });
      }
      
      // Try Cloudinary first for videos
      const cloudinaryFilename = `vid_${Date.now()}_${Math.random().toString(36).substring(7)}`;
      const cloudinaryResult = await uploadVideoToCloudinary(buffer, cloudinaryFilename);
      
      if (cloudinaryResult.success && cloudinaryResult.url && cloudinaryResult.publicId) {
        return NextResponse.json({ 
          success: true, 
          url: cloudinaryResult.url,
          publicId: cloudinaryResult.publicId,
          type: 'video',
          source: 'cloudinary'
        });
      }
      
      // Fallback to local upload if Cloudinary fails
      console.log('Cloudinary upload failed, falling back to local storage');
      
      const filename = `vid_${Date.now()}_${Math.random().toString(36).substring(7)}.${ext}`;
      const filepath = path.join(uploadDir, filename);
      
      await writeFile(filepath, buffer);
      
      return NextResponse.json({ 
        success: true, 
        filename, 
        type: 'video',
        source: 'local'
      });
    }
    
    return NextResponse.json({ success: false, error: 'No file provided' }, { status: 400 });
  } catch (error) {
    console.error('Error uploading file:', error);
    return NextResponse.json({ success: false, error: 'Failed to upload file' }, { status: 500 });
  }
}