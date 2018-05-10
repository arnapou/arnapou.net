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

public class MathTools
{
	#region length
	static public double getLength(System.Windows.Point p1, System.Windows.Point p2)
	{
		return getLength(p1.X, p1.Y, p2.X, p2.Y);
	}
	static public double getLength(System.Drawing.Point p1, System.Drawing.Point p2)
	{
		return getLength(p1.X, p1.Y, p2.X, p2.Y);
	}

	static public double getLength(double x1, double y1, double x2, double y2)
	{
		return Math.Sqrt((x2 - x1) * (x2 - x1) + (y2 - y1) * (y2 - y1));
	}
	#endregion

	#region angle

	static public double deg2rad(double deg)
	{
		return deg * Math.PI / 180;
	}

	static public double rad2deg(double rad)
	{
		return rad * 180 / Math.PI;
	}
	/// <summary>
	/// Return the Angle as degrees
	/// </summary>
	static public double getAngle(double x1, double y1, double x2, double y2)
	{
		double angle = 0;
		if (x1 == x2)
		{
			if (y1 < y2)
			{
				angle = Math.PI / 2;
			}
			else if (y1 > y2)
			{
				angle = -Math.PI / 2;
			}
		}
		else
		{
			double m = (y2 - y1) / (x2 - x1);
			angle = Math.Atan(m);
			if (x1 > x2)
			{
				angle += Math.PI;
			}
		}
		return angle * 180 / Math.PI;
	}

	/// <summary>
	/// Return the Angle as degrees
	/// </summary>
	static public double getAngle(System.Windows.Point p1, System.Windows.Point p2)
	{
		return getAngle(p1.X, p1.Y, p2.X, p2.Y);
	}

	/// <summary>
	/// Return the Angle as degrees
	/// </summary>
	static public double getAngle(System.Drawing.Point p1, System.Drawing.Point p2)
	{
		return getAngle(p1.X, p1.Y, p2.X, p2.Y);
	}
	#endregion

}