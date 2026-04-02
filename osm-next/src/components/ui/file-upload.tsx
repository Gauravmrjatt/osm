"use client"

import * as React from "react"
import { Upload, X, Image, Video, File } from "lucide-react"
import { cn } from "@/lib/utils"
import { Progress } from "./progress"

interface FileUploadProps {
  accept: string
  label: string
  value?: string
  onChange: (filename: string, source?: 'cloudinary' | 'local', publicId?: string) => void
  maxSize?: number // in MB
  fieldName?: 'logo_image' | 'banner_image' | 'video_file'
}

export function FileUpload({ accept, label, value, onChange, maxSize = 10, fieldName = 'logo_image' }: FileUploadProps) {
  const [dragActive, setDragActive] = React.useState(false)
  const [progress, setProgress] = React.useState(0)
  const [uploading, setUploading] = React.useState(false)
  const [preview, setPreview] = React.useState<string | null>(null)
  const [fileSource, setFileSource] = React.useState<'cloudinary' | 'local' | null>(null)
  const inputRef = React.useRef<HTMLInputElement>(null)

  const isVideo = accept.includes("video")
  const isImage = accept.includes("image")

  React.useEffect(() => {
    if (value) {
      setPreview(value)
    }
  }, [value])

  const handleUpload = async (file: File) => {
    if (!file) return

    setUploading(true)
    setProgress(0)

    const formData = new FormData()
    if (isImage && fieldName === 'banner_image') {
      formData.append("banner_image", file)
    } else if (isVideo) {
      formData.append("video_file", file)
    } else {
      formData.append("logo_image", file)
    }

    // Simulate progress
    const progressInterval = setInterval(() => {
      setProgress(prev => {
        if (prev >= 90) {
          clearInterval(progressInterval)
          return prev
        }
        return prev + 10
      })
    }, 100)

    try {
      const res = await fetch("/api/upload", {
        method: "POST",
        body: formData,
      })

      clearInterval(progressInterval)

      if (res.ok) {
        const data = await res.json()
        if (data.success) {
          setProgress(100)
          
          const source = data.source || 'local'
          const publicId = data.publicId || ''
          
          // For cloudinary, use the URL directly; for local, use filename
          if (source === 'cloudinary' && data.url) {
            setPreview(data.url)
            onChange(data.url, source, publicId)
          } else {
            setPreview(data.filename)
            onChange(data.filename, source, publicId)
          }
          
          setFileSource(source)
          
          setTimeout(() => {
            setUploading(false)
            setProgress(0)
          }, 500)
        } else {
          setUploading(false)
          setProgress(0)
          alert(data.error || "Upload failed")
        }
      } else {
        setUploading(false)
        setProgress(0)
        alert("Upload failed")
      }
    } catch (error) {
      clearInterval(progressInterval)
      setUploading(false)
      setProgress(0)
      console.error("Upload error:", error)
    }
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      if (file.size > maxSize * 1024 * 1024) {
        alert(`File size must be less than ${maxSize}MB`)
        return
      }
      handleUpload(file)
    }
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    setDragActive(false)
    const file = e.dataTransfer.files?.[0]
    if (file) {
      if (file.size > maxSize * 1024 * 1024) {
        alert(`File size must be less than ${maxSize}MB`)
        return
      }
      handleUpload(file)
    }
  }

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault()
    setDragActive(true)
  }

  const handleDragLeave = () => {
    setDragActive(false)
  }

  const removeFile = () => {
    setPreview(null)
    setFileSource(null)
    setVideoPublicId('')
    onChange("")
    if (inputRef.current) {
      inputRef.current.value = ""
    }
  }

  return (
    <div className="space-y-2">
      <label className="block text-sm font-medium text-[var(--foreground)]">{label}</label>
      
      {preview ? (
        <div className="relative border border-[var(--border-color)] rounded-[var(--radius-sm)] p-3 bg-[var(--muted)]">
          <div className="flex items-center gap-3">
            {isImage && (
              <img 
                src={fileSource === 'cloudinary' ? preview : `/api/files/${preview}`} 
                alt="Preview" 
                className="w-16 h-16 object-cover rounded-lg"
              />
            )}
            {isVideo && (
              <video 
                src={fileSource === 'cloudinary' ? preview : `/api/files/${preview}`} 
                className="w-24 h-16 object-cover rounded-lg"
              />
            )}
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium truncate text-[var(--foreground)]">{preview}</p>
              <p className="text-xs text-[var(--muted-foreground)]">
                {isImage ? "Image uploaded" : isVideo ? "Video uploaded" : "File uploaded"}
              </p>
            </div>
            <button
              type="button"
              onClick={removeFile}
              className="p-1.5 rounded-full bg-[var(--destructive)] text-white hover:bg-[var(--destructive)]/90"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
          
          {uploading && (
            <div className="absolute inset-0 bg-[var(--muted)]/80 rounded-[var(--radius-sm)] flex items-center justify-center">
              <div className="w-3/4">
                <Progress value={progress} className="h-2" />
                <p className="text-xs text-center mt-1 text-[var(--foreground)]">{progress}%</p>
              </div>
            </div>
          )}
        </div>
      ) : (
        <div
          className={cn(
            "border-2 border-dashed rounded-[var(--radius-sm)] p-6 text-center transition-colors cursor-pointer",
            dragActive 
              ? "border-[var(--primary)] bg-[var(--primary)]/10" 
              : "border-[var(--border-color)] hover:border-[var(--primary)]/50"
          )}
          onClick={() => inputRef.current?.click()}
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
        >
          <input
            ref={inputRef}
            type="file"
            accept={accept}
            onChange={handleChange}
            className="hidden"
          />
          
          {uploading ? (
            <div className="space-y-2">
              <Progress value={progress} className="h-2" />
              <p className="text-sm text-[var(--foreground)]">Uploading... {progress}%</p>
            </div>
          ) : (
            <div className="space-y-2">
              <div className="mx-auto w-10 h-10 rounded-full bg-[var(--accent)] flex items-center justify-center">
                {isImage ? (
                  <Image className="w-5 h-5 text-[var(--primary)]" />
                ) : isVideo ? (
                  <Video className="w-5 h-5 text-[var(--primary)]" />
                ) : (
                  <File className="w-5 h-5 text-[var(--primary)]" />
                )}
              </div>
              <p className="text-sm text-[var(--foreground)]">
                <span className="font-medium text-[var(--primary)]">Click to upload</span> or drag and drop
              </p>
              <p className="text-xs text-[var(--muted-foreground)]">
                {isImage ? "PNG, JPG, GIF, WebP up to" : isVideo ? "MP4, WebM up to" : "File up to"} {maxSize}MB
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
