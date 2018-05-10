using System;
using System.Collections.Generic;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Controls.Primitives;
using System.Windows.Media;
using System.Diagnostics;
using Microsoft.Surface.Presentation;
using Microsoft.Surface.Presentation.Controls.Primitives;
using Microsoft.Surface.Presentation.Controls;

namespace Arnapou
{
    public class ArcLoopingPanel : Panel, ISurfaceScrollInfo
    {

        private int maxSize = 100000000;

        protected Size totalContentSize = new Size();

        public double totalContentWidth
        {
            get { return totalContentSize.Width; }
        }

        public double totalContentHeight
        {
            get { return totalContentSize.Height; }
        }


        #region properties
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double _AngleDelta = 30; // degree entre 0 et 90
        public double AngleDelta
        {
            get { return _AngleDelta; }
            set
            {
                if (value > 0 && value <= 90)
                {
                    _AngleDelta = value;
                }
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private double _InnerMargin = 10;
        public double InnerMargin
        {
            get { return _InnerMargin; }
            set { _InnerMargin = value; InvalidateArrange(); }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private VerticalAlignment _VerticalContentAlignment = VerticalAlignment.Bottom;
        public VerticalAlignment VerticalContentAlignment
        {
            get { return _VerticalContentAlignment; }
            set { _VerticalContentAlignment = value; InvalidateArrange(); }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private bool _TopBottom = true;
        public bool TopBottom
        {
            get { return _TopBottom; }
            set { _TopBottom = value; InvalidateArrange(); }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region misc functions
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double Rad2Deg(double rad)
        {
            return rad * 180 / Math.PI;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double Deg2Rad(double deg)
        {
            return deg * Math.PI / 180;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region panel Override
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected override Size MeasureOverride(Size availableSize)
        {

            if (double.IsInfinity(availableSize.Height) || double.IsInfinity(availableSize.Width))
            {
                return new Size(1, 1);
            }

            if (this.InternalChildren.Count == 0)
            {
                return availableSize;
            }

            _viewport = availableSize;
            _viewportOffset = new Point(maxSize, maxSize);
            _extent = availableSize;

            foreach (UIElement child in this.InternalChildren)
            {
                child.Measure(availableSize);
                if (totalContentSize.Height < child.DesiredSize.Height)
                {
                    totalContentSize.Height = child.DesiredSize.Height;
                }
                totalContentSize.Width += child.DesiredSize.Width;
            }

            return availableSize;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected override Size ArrangeOverride(Size finalSize)
        {
            UIElement child = null;
            int i, n;
            double ecart, pos, xpos, x, y, angle, radius;
            Point RotateCenter = new Point(0.5, 0.5);

            n = InternalChildren.Count;


            if (_VerticalContentAlignment == VerticalAlignment.Top)
            {
                RotateCenter = new Point(0.5, 0);
            }
            else if (_VerticalContentAlignment == VerticalAlignment.Bottom)
            {
                RotateCenter = new Point(0.5, 1);
            }

            ecart = (_viewportOffset.X - maxSize) % totalContentSize.Width;
            if (ecart < 0)
            {
                ecart += totalContentSize.Width;
                ecart = ecart % totalContentSize.Width;
            }

            radius = _viewport.Width / (2 * Deg2Rad(_AngleDelta));
            xpos = _viewport.Width / 2 - totalContentSize.Width / 2 - ecart;

            for (i = 0; i < n; i++)
            {
                child = InternalChildren[i];
                if (xpos >= -child.DesiredSize.Width)
                {
                    pos = xpos;
                }
                else if (xpos + totalContentSize.Width >= -child.DesiredSize.Width)
                {
                    pos = xpos + totalContentSize.Width;
                }
                else if (xpos > _viewport.Width && xpos - totalContentSize.Width >= -child.DesiredSize.Width)
                {
                    pos = xpos - totalContentSize.Width;
                }
                else
                {
                    pos = xpos;
                }
                angle = (pos + child.DesiredSize.Width / 2 - _viewport.Width / 2) / radius;
                if (Math.Abs(angle) <= Math.PI / 2)
                {
                    x = radius * Math.Sin(angle);
                    y = radius * (1 - Math.Cos(angle));
                    x = _viewport.Width / 2 + x - child.DesiredSize.Width / 2;
                    y += _InnerMargin;
                    if (!_TopBottom)
                    {
                        y = -y;
                    }
                    if (_VerticalContentAlignment == VerticalAlignment.Bottom)
                    {
                        y += totalContentSize.Height - child.DesiredSize.Height;
                    }
                    else if (_VerticalContentAlignment != VerticalAlignment.Top)
                    {
                        y += (totalContentSize.Height - child.DesiredSize.Height) / 2;
                    }
                    if (_TopBottom)
                    {
                        child.RenderTransform = new RotateTransform(Rad2Deg(angle));
                    }
                    else
                    {
                        child.RenderTransform = new RotateTransform(Rad2Deg(-angle));
                        y += _viewport.Height - totalContentSize.Height;
                    }
                    child.RenderTransformOrigin = RotateCenter;
                    child.Arrange(new Rect(x, y, child.DesiredSize.Width, child.DesiredSize.Height));
                }
                else
                {
                    child.Arrange(new Rect(0, 0, 0, 0));
                }
                xpos += child.DesiredSize.Width;
            }

            if (_ScrollOwner != null)
            {
                _ScrollOwner.InvalidateScrollInfo();
            }
            return finalSize;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region ISurfaceScrollInfo Membres
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public Vector ConvertFromViewportUnits(Point origin, Vector offset)
        {
            return offset;
        }

        public Vector ConvertToViewportUnits(Point origin, Vector offset)
        {
            return offset;
        }

        public Vector ConvertToViewportUnitsForFlick(Point origin, Vector offset)
        {
            return offset;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region IScrollInfo Membres
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public Rect MakeVisible(Visual visual, Rect rectangle)
        {
            return rectangle;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void LineLeft()
        {
            SetHorizontalOffset(this.HorizontalOffset - 1);
        }

        public void LineRight()
        {
            SetHorizontalOffset(this.HorizontalOffset + 1);
        }

        public void LineDown()
        {
            SetVerticalOffset(this.VerticalOffset + 1);
        }

        public void LineUp()
        {
            SetVerticalOffset(this.VerticalOffset - 1);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void PageLeft()
        {
            SetHorizontalOffset(this.HorizontalOffset - 10);
        }

        public void PageRight()
        {
            SetHorizontalOffset(this.HorizontalOffset + 10);
        }

        public void PageDown()
        {
            SetVerticalOffset(this.VerticalOffset + 10);
        }

        public void PageUp()
        {
            SetVerticalOffset(this.VerticalOffset - 10);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MouseWheelLeft()
        {
            SetHorizontalOffset(this.HorizontalOffset - 10);
        }

        public void MouseWheelRight()
        {
            SetHorizontalOffset(this.HorizontalOffset + 10);
        }

        public void MouseWheelDown()
        {
            SetVerticalOffset(this.VerticalOffset + 10);
        }

        public void MouseWheelUp()
        {
            SetVerticalOffset(this.VerticalOffset - 10);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void SetHorizontalOffset(double offset)
        {
            if (totalContentSize.Width > _viewport.Width)
            {
                _viewportOffset.X = offset;
                InvalidateArrange();
            }
        }

        public void SetVerticalOffset(double offset)
        {
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private bool _CanHorizontallyScroll = false;
        public bool CanHorizontallyScroll
        {
            get { return _CanHorizontallyScroll; }
            set { _CanHorizontallyScroll = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private bool _CanVerticallyScroll = false;
        public bool CanVerticallyScroll
        {
            get { return _CanVerticallyScroll; }
            set { _CanVerticallyScroll = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private Size _extent = new Size(0, 0);
        public double ExtentHeight
        {
            get { return _extent.Height; }
        }

        public double ExtentWidth
        {
            get { return _extent.Width; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private ScrollViewer _ScrollOwner;
        public ScrollViewer ScrollOwner
        {
            get { return _ScrollOwner; }
            set { _ScrollOwner = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private Point _viewportOffset;
        public double HorizontalOffset
        {
            get { return _viewportOffset.X; }
        }

        public double VerticalOffset
        {
            get { return _viewportOffset.Y; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        private Size _viewport = new Size(0, 0);
        public double ViewportHeight
        {
            get { return _viewport.Height; }
        }

        public double ViewportWidth
        {
            get { return _viewport.Width; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion
    }
}

