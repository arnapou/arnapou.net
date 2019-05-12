using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Drawing;
using System.Drawing.Imaging;
using System.Windows.Media.Imaging;
using System.Diagnostics;
using System.Drawing.Drawing2D;

public class MediaBitmap
{
	#region resize


	static public Bitmap ResizeFit(Bitmap bitmap, int destWidthOrHeight)
	{
		return ResizeFit(bitmap, destWidthOrHeight, destWidthOrHeight);
	}

	static public Bitmap ResizeFit(Bitmap bitmap, int destWidth, int destHeight)
	{
		if (bitmap == null) return null;
		int w = bitmap.Width;
		int h = bitmap.Height;
		if (w / h > destWidth / destHeight)
		{
			return ResizeFill(bitmap, destWidth, destWidth * h / w);
		}
		else
		{
			return ResizeFill(bitmap, destHeight * w / h, destHeight);
		}
	}

	static public Bitmap ResizeFill(Bitmap bitmap, int destWidthOrHeight)
	{
		return ResizeFill(bitmap, destWidthOrHeight, destWidthOrHeight);
	}

	static public Bitmap ResizeFill(Bitmap bitmap, int destWidth, int destHeight)
	{
		if (bitmap == null) { return null; }
		Bitmap b = new Bitmap(destWidth, destHeight);
		Graphics g = Graphics.FromImage((System.Drawing.Image)b);
		g.InterpolationMode = System.Drawing.Drawing2D.InterpolationMode.HighQualityBicubic;
		g.DrawImage((System.Drawing.Image)bitmap, 0, 0, destWidth, destHeight);
		g.Dispose();
		return b;
	}

	static public Bitmap ResizeHeight(Bitmap bitmap, int destHeight)
	{
		return ResizeFit(bitmap, 9999999, destHeight);
	}

	static public Bitmap ResizeWidth(Bitmap bitmap, int destWidth)
	{
		return ResizeFit(bitmap, destWidth, 9999999);
	}
	#endregion

	#region effect grayscale
	static public Bitmap EffectGrayscale(Bitmap bitmap)
	{
		return EffectGrayscale(bitmap, .3f, .59f, .11f);
	}

	static public Bitmap EffectGrayscale(Bitmap bitmap, float red, float green, float blue)
	{
		if (bitmap == null) { return null; }
		Bitmap newBitmap = new Bitmap(bitmap.Width, bitmap.Height);
		Graphics g = Graphics.FromImage(newBitmap);
		ColorMatrix colorMatrix = new ColorMatrix(
			new float[][] {
				new float[] {red, red, red, 0, 0},
				new float[] {green, green, green, 0, 0},
				new float[] {blue, blue, blue, 0, 0},
				new float[] {0, 0, 0, 1, 0},
				new float[] {0, 0, 0, 0, 1}
			}
		);
		ImageAttributes attributes = new ImageAttributes();
		attributes.SetColorMatrix(colorMatrix);

		g.DrawImage(bitmap,
			new Rectangle(0, 0, bitmap.Width, bitmap.Height),
		   0, 0, bitmap.Width, bitmap.Height, GraphicsUnit.Pixel, attributes
		);

		g.Dispose();
		return newBitmap;
	}

	#endregion

	#region effect threshold
	public static Bitmap EffectThreshold(Bitmap bitmap)
	{
		return EffectThreshold(bitmap, 128);
	}

	public static Bitmap EffectThreshold(Bitmap bitmap, int seuil)
	{
		return EffectThreshold(bitmap, seuil, .35, .35, .3);
	}

	public static Bitmap EffectThreshold(Bitmap bitmap, int seuil, double red, double green, double blue)
	{
		if (bitmap == null) return null;
		try
		{
			unsafe
			{
				int w = bitmap.Width;
				int h = bitmap.Height;
				int x, y, PixelSize;

				double s = (double)seuil;
				double v;

				byte* pIn, pOut;

				BitmapData bmdIn = bitmap.LockBits(new Rectangle(0, 0, w, h), ImageLockMode.ReadOnly, bitmap.PixelFormat);
				Bitmap bm = new Bitmap(w, h, bitmap.PixelFormat);
				BitmapData bmdOut = bm.LockBits(new Rectangle(0, 0, w, h), ImageLockMode.WriteOnly, bitmap.PixelFormat);

				if (bitmap.PixelFormat == PixelFormat.Format32bppArgb) { PixelSize = 4; }
				else if (bitmap.PixelFormat == PixelFormat.Format24bppRgb) { PixelSize = 3; }
				else
				{
					bitmap.UnlockBits(bmdIn);
					bitmap.UnlockBits(bmdOut);
					return null;
				}
				
				int scanlineIn = bmdIn.Stride - w * PixelSize;
				int scanlineOut = bmdOut.Stride - w * PixelSize;

				pIn = (byte*)bmdIn.Scan0;
				pOut = (byte*)bmdOut.Scan0;
				for (y = 0; y < h; y++)
				{
					for (x = 0; x < w; x++)
					{
						v = red * (double)(pIn[2]); ; // R
						v += green * (double)(pIn[1]); ; // G
						v += blue * (double)(pIn[0]); ; // B
						if (v > s)
						{
							pOut[2] = 0xFF;
							pOut[1] = 0xFF;
							pOut[0] = 0xFF;
						}
						else
						{
							pOut[2] = 0x00;
							pOut[1] = 0x00;
							pOut[0] = 0x00;
						}
						pIn += PixelSize;
						pOut += PixelSize;
					}
					pIn += scanlineIn;
					pOut += scanlineOut;
				}

				bitmap.UnlockBits(bmdIn);
				bm.UnlockBits(bmdOut);

				return bm;
			}
		}
		catch (Exception) { return null; }
	}
	#endregion
	
	#region effect normalize
	public static Bitmap EffectNormalizeMinMax(Bitmap bitmap)
	{
		return EffectNormalize(bitmap, -2, 1.0);
	}

	public static Bitmap EffectNormalizeMinMax(Bitmap bitmap, double factor)
	{
		return EffectNormalize(bitmap, -2, factor);
	}

	public static Bitmap EffectNormalize(Bitmap bitmap)
	{
		return EffectNormalize(bitmap, -1, 1.0);
	}

	public static Bitmap EffectNormalize(Bitmap bitmap, double factor)
	{
		return EffectNormalize(bitmap, -1, factor);
	}
	
	public static Bitmap EffectNormalize(Bitmap bitmap, double center, double factor)
	{
		if (bitmap == null) { return null; }


		try
		{
			unsafe
			{
				int w = bitmap.Width;
				int h = bitmap.Height;
				int x, y, PixelSize, min = 9999, max = 0, s = 0, v;

				byte r, g, b;
				double R, G, B;

				byte* pIn, pOut;

				BitmapData bmdIn = bitmap.LockBits(new Rectangle(0, 0, w, h), ImageLockMode.ReadOnly, bitmap.PixelFormat);
				Bitmap bm = new Bitmap(w, h);
				BitmapData bmdOut = bm.LockBits(new Rectangle(0, 0, w, h), ImageLockMode.WriteOnly, bm.PixelFormat);

				if (bitmap.PixelFormat == PixelFormat.Format32bppArgb) { PixelSize = 4; }
				else if (bitmap.PixelFormat == PixelFormat.Format24bppRgb) { PixelSize = 3; }
				else
				{
					bitmap.UnlockBits(bmdIn);
					bitmap.UnlockBits(bmdOut);
					return null;
				}

				int scanlineIn = bmdIn.Stride - w * PixelSize;
				int scanlineOut = bmdOut.Stride - w * PixelSize;

				// detect min and max
				pIn = (byte*)bmdIn.Scan0;
				pOut = (byte*)bmdOut.Scan0;
				for (y = 0; y < h; y++)
				{
					for (x = 0; x < w; x++)
					{
						v = pIn[0] + pIn[1] + pIn[2];
						if (v > max) { max = v; } else if (v < min) { min = v; }
						s += v;
						pIn += PixelSize;
					}
					pIn += scanlineIn;
				}

				if (max <= min) { return null; }

				max = (int)((double)max / (double)3);
				min = (int)((double)min / (double)3);

				double scale = (double)255 / (double)(max - min);
				if (center == -1)
				{
					// baricentre de tous les pixels
					center = s / ((double)(w * h) * (double)3);
				}
				else if (center == -2)
				{
					// baricentre min et max
					center = ((double)255 - scale * (double)max) / ((double)1 - scale);
				}
				scale *= factor;

				// normalize
				pIn = (byte*)bmdIn.Scan0;
				pOut = (byte*)bmdOut.Scan0;
				for (y = 0; y < h; y++)
				{
					for (x = 0; x < w; x++)
					{
						r = pIn[2];
						g = pIn[1];
						b = pIn[0];

						if ((double)r > center) { R = (center + scale * ((double)r - center)); }
						else { R = (center - scale * (center - (double)r)); }
						if (R < 0) { R = 0; } else if (R > 255) { R = 255; }

						if ((double)g > center) { G = (center + scale * ((double)g - center)); }
						else { G = (center - scale * (center - (double)g)); }
						if (G < 0) { G = 0; } else if (G > 255) { G = 255; }

						if ((double)b > center) { B = (center + scale * ((double)b - center)); }
						else { B = (center - scale * (center - (double)b)); }
						if (B < 0) { B = 0; } else if (B > 255) { B = 255; }

						pOut[2] = (byte)R;
						pOut[1] = (byte)G;
						pOut[0] = (byte)B;

						pIn += PixelSize;
						pOut += PixelSize;
					}
					pIn += scanlineIn;
					pOut += scanlineOut;
				}

				bm.UnlockBits(bmdOut);
				bitmap.UnlockBits(bmdIn);

				return bm;
			}
		}
		catch (Exception) { return null; }
	}

	#endregion

	#region divers
	public static void SaveJpeg(string path, System.Drawing.Image image, int quality)
	{
		//ensure the quality is within the correct range
		if ((quality < 0) || (quality > 100))
		{
			//create the error message
			string error = string.Format("Jpeg image quality must be between 0 and 100, with 100 being the highest quality.  A value of {0} was specified.", quality);
			//throw a helpful exception
			throw new ArgumentOutOfRangeException(error);
		}

		//create an encoder parameter for the image quality
		EncoderParameter qualityParam = new EncoderParameter(System.Drawing.Imaging.Encoder.Quality, quality);
		//get the jpeg codec
		ImageCodecInfo jpegCodec = GetEncoderInfo("image/jpeg");

		//create a collection of all parameters that we will pass to the encoder
		EncoderParameters encoderParams = new EncoderParameters(1);
		//set the quality parameter for the codec
		encoderParams.Param[0] = qualityParam;
		//save the image using the codec and the parameters
		image.Save(path, jpegCodec, encoderParams);
	}
	public static ImageCodecInfo GetEncoderInfo(string mimeType)
	{
		//do a case insensitive search for the mime type
		string lookupKey = mimeType.ToLower();

		//the codec to return, default to null
		ImageCodecInfo foundCodec = null;

		//if we have the encoder, get it to return
		if (Encoders.ContainsKey(lookupKey))
		{
			//pull the codec from the lookup
			foundCodec = Encoders[lookupKey];
		}

		return foundCodec;
	}
	private static Dictionary<string, ImageCodecInfo> encoders = null;

	/// <summary>
	/// A quick lookup for getting image encoders
	/// </summary>
	public static Dictionary<string, ImageCodecInfo> Encoders
	{
		//get accessor that creates the dictionary on demand
		get
		{
			//if the quick lookup isn't initialised, initialise it
			if (encoders == null)
			{
				encoders = new Dictionary<string, ImageCodecInfo>();
			}

			//if there are no codecs, try loading them
			if (encoders.Count == 0)
			{
				//get all the codecs
				foreach (ImageCodecInfo codec in ImageCodecInfo.GetImageEncoders())
				{
					//add each codec to the quick lookup
					encoders.Add(codec.MimeType.ToLower(), codec);
				}
			}

			//return the lookup
			return encoders;
		}
	}

	static public Bitmap fromBitmapSource(BitmapSource bitmapSource)
	{
		return MediaImage.toBitmap(bitmapSource);
	}

	static public BitmapSource toBitmapSource(Bitmap bitmap)
	{
		if (bitmap == null) return null;
		using (MemoryStream stream = new MemoryStream())
		{
			bitmap.Save(stream, ImageFormat.Jpeg);
			stream.Position = 0;
			BitmapImage result = new BitmapImage();
			result.BeginInit();
			// According to MSDN, "The default OnDemand cache option retains access to the stream until the image is needed."
			// Force the bitmap to load right now so we can dispose the stream.
			result.CacheOption = BitmapCacheOption.OnLoad;
			result.StreamSource = stream;
			result.EndInit();
			result.Freeze();
			stream.Close();
			stream.Dispose();
			GC.Collect(0, GCCollectionMode.Forced);
			return result;
		}
	}

	static public Bitmap fromFile(string filename)
	{
		return MediaImage.toBitmap(MediaImage.fromFile(filename));
	}

	#endregion

	#region autocrop
	public static Rectangle AutoCropRect(System.Drawing.Bitmap bitmap)
	{
		return AutoCropRect(bitmap, Color.White);
	}

	public static Rectangle AutoCropRect(System.Drawing.Bitmap bitmap, System.Drawing.Color color)
	{
		return AutoCropRect(bitmap, color, 128);
	}

	public static Rectangle AutoCropRect(System.Drawing.Bitmap bitmap, System.Drawing.Color color, int seuil)
	{
		try
		{
			unsafe
			{
				int x, y, xMin, xMax, yMin, yMax, dR, dG, dB, cR, cG, cB, w, h, PixelSize, scanline, stride;
				bool broken;

				byte* p;

				// aide utile http://www.bobpowell.net/lockingbits.htm
				System.Drawing.Imaging.BitmapData bmd = bitmap.LockBits(
					new System.Drawing.Rectangle(0, 0, bitmap.Width, bitmap.Height),
					System.Drawing.Imaging.ImageLockMode.ReadOnly,
					bitmap.PixelFormat);

				if (bitmap.PixelFormat == System.Drawing.Imaging.PixelFormat.Format32bppArgb)
				{
					PixelSize = 4;
				}
				else if (bitmap.PixelFormat == System.Drawing.Imaging.PixelFormat.Format24bppRgb)
				{
					PixelSize = 3;
				}
				else
				{
					bitmap.UnlockBits(bmd);
					return Rectangle.Empty;
				}

				w = bmd.Width;
				h = bmd.Height;

				yMin = h;
				yMax = 0;
				xMin = w;
				xMax = 0;

				cR = color.R;
				cG = color.G;
				cB = color.B;

				stride = bmd.Stride;
				scanline = stride - bmd.Width * PixelSize;

				// detection bord haut
				broken = false;
				p = (byte*)bmd.Scan0;
				for (y = 0; y < h; y++)
				{
					for (x = 0; x < w; x++)
					{
						dR = p[2] - cR;
						dG = p[1] - cG;
						dB = p[0] - cB;
						if (dR > seuil || dR < -seuil || dG > seuil || dG < -seuil || dB > seuil || dB < -seuil)
						{
							yMin = y;
							broken = true;
							break;
						}
						p += PixelSize;
					}
					if (broken) { break; }
					p += scanline;
				}
				if (yMin == h)
				{
					// gros pb de detection : exemple image unie ...
					bitmap.UnlockBits(bmd);
					return Rectangle.Empty;
				}

				// detection bord bas
				broken = false;
				p = (byte*)bmd.Scan0 + (h - 1) * stride;
				for (y = h - 1; y >= 0; y--)
				{
					for (x = 0; x < w; x++)
					{
						dR = p[2] - cR;
						dG = p[1] - cG;
						dB = p[0] - cB;
						if (dR > seuil || dR < -seuil || dG > seuil || dG < -seuil || dB > seuil || dB < -seuil)
						{
							yMax = y;
							broken = true;
							break;
						}
						p += PixelSize;
					}
					if (broken) { break; }
					if (y <= yMin) { yMax = y; break; }
					p -= stride + PixelSize * w;
				}

				if (yMin >= yMax)
				{
					// gros pb de detection : exemple image unie ...
					bitmap.UnlockBits(bmd);
					return Rectangle.Empty;
				}

				// detection bord gauche
				broken = false;
				for (x = 0; x < w; x++)
				{
					p = (byte*)bmd.Scan0 + yMin * stride;
					for (y = yMin; y <= yMax; y++)
					{
						dR = p[x * PixelSize + 2] - cR;
						dG = p[x * PixelSize + 1] - cG;
						dB = p[x * PixelSize] - cB;
						if (dR > seuil || dR < -seuil || dG > seuil || dG < -seuil || dB > seuil || dB < -seuil)
						{
							xMin = x;
							broken = true;
							break;
						}
						p += stride;
					}
					if (broken) { break; }
				}
				if (xMin == w)
				{
					// gros pb de detection : exemple image unie ...
					bitmap.UnlockBits(bmd);
					return Rectangle.Empty;
				}

				// detection bord droit
				broken = false;
				for (x = w - 1; x >= 0; x--)
				{
					p = (byte*)bmd.Scan0 + yMin * stride;
					for (y = yMin; y <= yMax; y++)
					{
						dR = p[x * PixelSize + 2] - cR;
						dG = p[x * PixelSize + 1] - cG;
						dB = p[x * PixelSize] - cB;
						if (dR > seuil || dR < -seuil || dG > seuil || dG < -seuil || dB > seuil || dB < -seuil)
						{
							xMax = x;
							broken = true;
							break;
						}
						p += stride;
					}
					if (broken) { break; }
					if (x <= xMin) { xMax = x; break; }
				}

				if (xMin >= xMax)
				{
					// gros pb de detection : exemple image unie ...
					bitmap.UnlockBits(bmd);
					return Rectangle.Empty;
				}

				bitmap.UnlockBits(bmd);

				return new System.Drawing.Rectangle(xMin, yMin, xMax - xMin + 1, yMax - yMin + 1);

			}
		}
		catch { return Rectangle.Empty; }
	}

	public static System.Drawing.Bitmap AutoCrop(System.Drawing.Bitmap bitmap)
	{
		return AutoCrop(bitmap, Color.White);
	}

	public static System.Drawing.Bitmap AutoCrop(System.Drawing.Bitmap bitmap, System.Drawing.Color color)
	{
		return AutoCrop(bitmap, color, 128);
	}

	public static System.Drawing.Bitmap AutoCrop(System.Drawing.Bitmap bitmap, System.Drawing.Color color, int seuil)
	{
		Rectangle rect = AutoCropRect(bitmap, color, seuil);
		if (rect == Rectangle.Empty) { return null; }
		Bitmap cropped = bitmap.Clone(rect, bitmap.PixelFormat);
		return cropped;
	}
	#endregion

	#region rotate
	static public Bitmap rotate(Bitmap bitmap, float theta)
	{
		return rotate(bitmap, theta, false, Color.White);
	}

	static public Bitmap rotate(Bitmap bitmap, float theta, bool enlarge)
	{
		return rotate(bitmap, theta, enlarge, Color.White);
	}

	static public Bitmap rotate(Bitmap bitmap, float theta, Color backgroundColor)
	{
		return rotate(bitmap, theta, true, backgroundColor);
	}

	static public Bitmap rotate(Bitmap bitmap, float theta, bool enlarge, Color backgroundColor)
	{
		int w = bitmap.Width;
		int h = bitmap.Height;
		float epsilon = .000001f;

		while (theta < 0) theta += 360;
		while (theta > 360) theta -= 360;
		if (theta < epsilon) return (Bitmap)bitmap.Clone();

		if (Math.Abs(theta - 90) < epsilon) {
			Bitmap newBmp = new Bitmap(h, w, bitmap.PixelFormat);
			Graphics g = Graphics.FromImage(newBmp);
			g.RotateTransform(90f);
			g.TranslateTransform(0, -(float)h);
			g.DrawImage(bitmap, new Point(0,0));
			g.Dispose();
			return newBmp;
		}
		else if (Math.Abs(theta - 180) < epsilon)
		{
			Bitmap newBmp = new Bitmap(w, h, bitmap.PixelFormat);
			Graphics g = Graphics.FromImage(newBmp);
			g.RotateTransform(180f);
			g.TranslateTransform(-(float)w, -(float)h);
			g.DrawImage(bitmap, new Point(0,0));
			g.Dispose();
			return newBmp;
		}
		else if (Math.Abs(theta - 270) < epsilon)
		{
			Bitmap newBmp = new Bitmap(h, w, bitmap.PixelFormat);
			Graphics g = Graphics.FromImage(newBmp);
			g.RotateTransform(270f);
			g.TranslateTransform(-(float)w, 0);
			g.DrawImage(bitmap, new Point(0,0));
			g.Dispose();
			return newBmp;
		}
		else
		{
			Matrix mRotate = new Matrix();
			mRotate.Translate(-(float)bitmap.Width / (float)2, -(float)bitmap.Height / (float)2, MatrixOrder.Append);
			mRotate.RotateAt(theta, new Point(0, 0), MatrixOrder.Append);

			using (GraphicsPath gp = new GraphicsPath())
			{  // transform image points by rotation matrix
				gp.AddPolygon(new Point[] { new Point(0, 0), new Point(bitmap.Width, 0), new Point(0, bitmap.Height) });
				gp.Transform(mRotate);
				PointF[] pts = gp.PathPoints;

				// create destination bitmap sized to contain rotated source image
				if (enlarge)
				{
					w = (int)(Math.Abs((double)bitmap.Width * Math.Cos((double)theta * Math.PI / 180)) + Math.Abs((double)bitmap.Height * Math.Sin((double)theta * Math.PI / 180)));
					h = (int)(Math.Abs((double)bitmap.Width * Math.Sin((double)theta * Math.PI / 180)) + Math.Abs((double)bitmap.Height * Math.Cos((double)theta * Math.PI / 180)));
				}

				Bitmap bmpDest = new Bitmap(w, h);

				using (Graphics gDest = Graphics.FromImage(bmpDest))
				{  // draw source into dest
					gDest.FillRectangle(Brushes.White, 0, 0, bmpDest.Width, bmpDest.Height);

					Matrix mDest = new Matrix();
					mDest.Translate(bmpDest.Width / 2, bmpDest.Height / 2, MatrixOrder.Append);
					gDest.Transform = mDest;
					gDest.DrawImage(bitmap, pts);
					gDest.Dispose();
					return bmpDest;
				}
			}
		}
	}

	#endregion rotate

	#region extract Rectangle
	static public Bitmap extractRectangle(Bitmap bitmap, double x1, double y1, double x2, double y2, double x3, double y3, double x4, double y4)
	{
		return extractRectangle(bitmap, (int)x1, (int)y1, (int)x2, (int)y2, (int)x3, (int)y3, (int)x4, (int)y4);
	}
	static public Bitmap extractRectangle(Bitmap bitmap, int x1, int y1, int x2, int y2, int x3, int y3, int x4, int y4)
	{
		return extractRectangle(bitmap, new Point(x1, y1), new Point(x2, y2), new Point(x3, y3), new Point(x4, y4));
	}
	static public Bitmap extractRectangle(Bitmap bitmap, System.Windows.Point p1, System.Windows.Point p2, System.Windows.Point p3, System.Windows.Point p4)
	{
		return extractRectangle(bitmap, p1.X, p1.Y, p2.X, p2.Y, p3.X, p3.Y, p4.X, p4.Y);
	}
	static public Bitmap extractRectangle(Bitmap bitmap, Point p1, Point p2, Point p3, Point p4) {
		try
		{
			List<Point> pts = new List<Point>();
			pts.Add(p1);
			pts.Add(p2);
			pts.Add(p3);
			pts.Add(p4);
			Point min = new Point(999999, 999999);
			Point max = new Point(0, 0);
			foreach (Point point in pts)
			{
				if (point.X < min.X) min.X = (int)point.X;
				if (point.Y < min.Y) min.Y = (int)point.Y;
				if (point.X > max.X) max.X = (int)point.X;
				if (point.Y > max.Y) max.Y = (int)point.Y;
			}

			Bitmap cropped = bitmap.Clone(new Rectangle(min.X, min.Y, max.X - min.X, max.Y - min.Y), bitmap.PixelFormat);

			double angle1 = (3600 + MathTools.getAngle(pts[0], pts[1])) % 360;
			double angle2 = (3600 + MathTools.getAngle(pts[1], pts[2]) - 90) % 360;
			double angle3 = (3600 + MathTools.getAngle(pts[2], pts[3]) - 180) % 360;
			double angle4 = (3600 + MathTools.getAngle(pts[3], pts[0]) - 270) % 360;
			double angle = (angle1 + angle2 + angle3 + angle4) / 4;
			if (Math.Abs(angle2 - angle1) > 5) angle = angle1;
			else if (Math.Abs(angle3 - angle1) > 5) angle = angle1;
			else if (Math.Abs(angle4 - angle1) > 5) angle = angle1;

			int length1 = (int)MathTools.getLength(pts[0], pts[1]);
			int length2 = (int)MathTools.getLength(pts[1], pts[2]);
			int length3 = (int)MathTools.getLength(pts[2], pts[3]);
			int length4 = (int)MathTools.getLength(pts[3], pts[0]);
			int lengthW = (length1 + length3) / 2;
			int lengthH = (length2 + length4) / 2;

			if ((double)Math.Abs(length1 - length3) > 0.05 * (double)Math.Max(length1, length3)) lengthW = Math.Max(length1, length3);
			if ((double)Math.Abs(length2 - length4) > 0.05 * (double)Math.Max(length2, length4)) lengthH = Math.Max(length2, length4);

			Bitmap rotated = MediaBitmap.rotate(cropped, -(float)angle, true);
			cropped.Dispose();
			Rectangle rect = new Rectangle((rotated.Width - lengthW) / 2, (rotated.Height - lengthH) / 2, lengthW, lengthH);
			if (rect.X < 0 || rect.Y < 0 || rect.Width > rotated.Width || rect.Height > rotated.Height || rect.X + rect.Width > rotated.Width || rect.Y + rect.Height > rotated.Height)
			{
				return rotated;
			}
			
			cropped = rotated.Clone(rect, rotated.PixelFormat);
			rotated.Dispose();
			return cropped;
		}
		catch (Exception)
		{
			return null;
		}
	}
	#endregion
}
