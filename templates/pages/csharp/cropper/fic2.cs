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

public class MediaImage
{
	#region divers
	static public BitmapSource fromFile(string filename)
	{
		try
		{
			FileStream fs = File.Open(filename, FileMode.Open);
			byte[] buffer = new byte[Convert.ToInt32(fs.Length)];
			fs.Read(buffer, 0, Convert.ToInt32(fs.Length));
			MemoryStream ms = new MemoryStream(buffer);
			BitmapImage image = new BitmapImage();
			image.BeginInit();
			image.StreamSource = ms;
			image.CacheOption = BitmapCacheOption.OnLoad;
			image.EndInit();
			image.Freeze();
			fs.Close();
			fs.Dispose();
			ms.Close();
			ms.Dispose();
			buffer = new byte[0];
			return image;
		}
		catch (Exception) { return null; }
	}

	static public BitmapSource fromBitmap(Bitmap bitmap)
	{
		return MediaBitmap.toBitmapSource(bitmap);
	}

	static public Bitmap toBitmap(BitmapSource bitmapSource)
	{
		if (bitmapSource == null) return null;
		using (MemoryStream stream = new MemoryStream())
		{
			BitmapEncoder enc = new BmpBitmapEncoder();
			enc.Frames.Add(BitmapFrame.Create(bitmapSource));
			enc.Save(stream);

			using (var tempBitmap = new Bitmap(stream))
			{
				// According to MSDN, one "must keep the stream open for the lifetime of the Bitmap."
				// So we return a copy of the new bitmap, allowing us to dispose both the bitmap and the stream.
				return new Bitmap(tempBitmap);
			}
		}
	}

	static public BitmapSource getThumbnail(string filename)
	{
		return getThumbnail(filename, 0);
	}

	static public BitmapSource getThumbnail(string filename, int maxHeight)
	{
		try
		{
			Bitmap bmp = MediaBitmap.fromFile(filename);
			Bitmap tn = MediaBitmap.ResizeHeight(bmp, maxHeight);
			BitmapSource image = MediaBitmap.toBitmapSource(tn);
			tn.Dispose();
			return image;
		}
		catch (Exception) { return null; }
	}
	#endregion
}