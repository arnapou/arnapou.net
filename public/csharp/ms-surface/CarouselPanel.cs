using System;
using System.Collections.Generic;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Controls.Primitives;
using System.Windows.Media;
using System.Windows.Media.Animation;
using System.Diagnostics;
using System.ComponentModel;
using Microsoft.Surface.Presentation;
using Microsoft.Surface.Presentation.Controls.Primitives;
using Microsoft.Surface.Presentation.Controls;

namespace Arnapou
{
    public class CarouselPanel : Panel, ISurfaceScrollInfo
    {

        #region Constructeur
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public CarouselPanel()
        {
            this.Loaded += new RoutedEventHandler(CarouselPanel_Loaded);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region auto center Events
        //- - - - - - - - - - - - - - - - - - - - - - - -
        void CarouselPanel_Loaded(object sender, RoutedEventArgs e)
        {
            SurfaceScrollViewer ssv = ScrollOwner as SurfaceScrollViewer;
            if (ssv != null)
            {
                PropertyDescriptor prop = TypeDescriptor.GetProperties(typeof(SurfaceScrollViewer))["IsScrolling"];
                prop.AddValueChanged(ssv, new EventHandler(this.ScrollEnded));
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        
        public void MoveToElementAnimation(UIElement sender)
        {
            int index = InternalChildren.IndexOf((UIElement)sender);
            double to = getOffsetFromIndex(InternalChildren.IndexOf((UIElement)sender));
            double from = getOffsetFromAngle(angleOffset);

            if (getBoundedIndex(activeIndex + 1) == index || getBoundedIndex(activeIndex - 1) == index)
            {
                MoveToOffset(to, true);
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void ScrollEnded(object sender, EventArgs e)
        {
            if (autoCenter)
            {
                SurfaceScrollViewer ssv = sender as SurfaceScrollViewer;
                if (ssv != null)
                {
                    if (!ssv.IsScrolling)
                    {
                        this.MoveCenter();
                    }
                }
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region carousel properties
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected bool initDone = false;
        protected Size _availableSize = new Size();
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected int maxSize = 1000000;
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double carouselPadding = 0;
        protected double angleEcart = 0;
        protected double angleOffset = 0;
        protected double radius = 0;
        protected int previousIndex = 0;
        protected int previousCount = 0;
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double _CenterScale = 1;
        public double CenterScale
        {
            get { return _CenterScale; }
            set { _CenterScale = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double _CenterScaleDuration = 500;
        public double CenterScaleDuration
        {
            get { return _CenterScaleDuration; }
            set { _CenterScaleDuration = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected UIElement _activeElement = null;
        public UIElement activeElement
        {
            get { return _activeElement; }
            set
            {
                int index = InternalChildren.IndexOf(value);
                _activeIndex = index;
                _activeElement = InternalChildren[index];
                previousIndex = _activeIndex;
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected int _activeIndex = 0;
        public int activeIndex
        {
            get { return _activeIndex; }
            set
            {
                if (value >= 0 && value < InternalChildren.Count)
                {
                    foreach (UIElement item in InternalChildren)
                    {
                        SurfaceListBoxItem r_item = item as SurfaceListBoxItem;
                        if (r_item != null)
                        {
                            if (r_item == activeElement)
                            {
                                r_item.IsSelected = true;
                            }
                            else
                            {
                                r_item.IsSelected = false;
                            }
                        }
                    }
                    _activeIndex = value;
                    _activeElement = InternalChildren[value];
                    previousIndex = _activeIndex;
                }
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected bool _isVertical = true;
        public bool isVertical
        {
            get { return _isVertical; }
            set { _isVertical = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected int _ItemsCount = 5;
        public int ItemsCount
        {
            get { return _ItemsCount; }
            set
            {
                if (value >= 3)
                {
                    _ItemsCount = value;
                    if (value % 2 == 0) { _ItemsCount--; }
                }
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected bool _autoCenter = true;
        public bool autoCenter
        {
            get { return _autoCenter; }
            set { _autoCenter = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region get methods
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected int getBoundedIndex(int index)
        {
            int n = InternalChildren.Count;
            int i = index;
            while (i < 0) { i += n; }
            while (i >= n) { i -= n; }
            return i;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double getOffsetFromAngle(double angle)
        {
            return angle * radius + maxSize / 2;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double getAngleFromOffset(double offset)
        {
            return (offset - maxSize / 2) / radius;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double getIndexFromOffset(double offset)
        {
            return getIndexFromAngle(getAngleFromOffset(offset));
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double getOffsetFromIndex(double index)
        {
            return getOffsetFromAngle(getAngleFromIndex(index));
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double getIndexFromAngle(double angle)
        {
            return InternalChildren.Count - 1 + angle / angleEcart;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected double getAngleFromIndex(double index)
        {
            return (index - InternalChildren.Count + 1) * angleEcart;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region move methods
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveCenter()
        {
            angleOffset = getAngleFromIndex(activeIndex);
            CarouselArrange(true);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToOffset(double offset, bool animate)
        {
            if (_isVertical) { _viewportOffset.Y = offset; } else { _viewportOffset.X = offset; }
            angleOffset = getAngleFromOffset(offset);
            CarouselArrange(animate);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToOffset(double offset)
        {
            MoveToOffset(offset, false);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToAngle(double angle, bool animate)
        {
            if (_isVertical) { _viewportOffset.Y = getOffsetFromAngle(angle); } else { _viewportOffset.X = getOffsetFromAngle(angle); }
            angleOffset = angle;
            CarouselArrange(animate);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToAngle(double angle)
        {
            MoveToAngle(angle, false);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToIndex(double index, bool animate)
        {
            angleOffset = getAngleFromIndex(index);
            if (_isVertical) { _viewportOffset.Y = getOffsetFromAngle(angleOffset); } else { _viewportOffset.X = getOffsetFromAngle(angleOffset); }
            CarouselArrange(animate);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToIndex(double index)
        {
            MoveToIndex(index, false);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToIndex(int index, bool animate)
        {
            angleOffset = getAngleFromIndex(index);
            if (_isVertical) { _viewportOffset.Y = getOffsetFromAngle(angleOffset); } else { _viewportOffset.X = getOffsetFromAngle(angleOffset); }
            CarouselArrange(animate);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToIndex(int index)
        {
            MoveToIndex(index, false);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void MoveToElement(UIElement item)
        {
            int index = InternalChildren.IndexOf(item);
            MoveToIndex(index);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region carousel methods
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void CarouselZIndex()
        {
            int n = InternalChildren.Count;
            int zn = (int)Math.Ceiling(n / 2.0);
            int prev, next, i;

            // z-index
            for (i = zn - 1; i > -1; i--)
            {
                prev = (activeIndex - zn + i + n) % n;
                next = (activeIndex + zn - i + n) % n;
                Panel.SetZIndex(InternalChildren[prev], i);
                Panel.SetZIndex(InternalChildren[next], i);
            }
            Panel.SetZIndex(InternalChildren[activeIndex], zn + 1);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void CarouselArrange()
        {
            CarouselArrange(false);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void CarouselArrange(bool animated)
        {
            if (InternalChildren.Count == 0) { return; }

            int i = 0;
            int n = InternalChildren.Count;
            int z = n-1;
            double realIndex = getIndexFromAngle(angleOffset);
            activeIndex = getBoundedIndex((int)Math.Floor(realIndex + 0.5));
            double deltaAngle = - angleEcart * (realIndex - (int)Math.Floor(realIndex + 0.5));

            // bornes
            int n1, n2, k1, k2;
            if (n % 2 == 1)
            {
                n1 = (int)Math.Floor(n / 2.0);
                n2 = n1;
            }
            else
            {
                n1 = (int)Math.Floor(n / 2.0);
                n2 = n1 - 1;
            }
            if (n <= ItemsCount)
            {
                k1 = n1;
                k2 = n2;
            }
            else if (n >= ItemsCount+2)
            {
                k1 = (int)Math.Floor(ItemsCount / 2.0) + 1;
                k2 = k1;
            }
            else
            {
                k1 = (int)Math.Floor(ItemsCount / 2.0);
                k2 = k1;
            }

            // affiche l'élément central
            CarouselArrangeItem(activeIndex, deltaAngle, z, animated);

            for (i = 1; i <= n1; i++)
            {
                // affiche les éléments de gauche
                z--;
                if (i <= k1)
                {
                    CarouselArrangeItem(activeIndex - i, deltaAngle - i * angleEcart, z, animated);
                }
                else
                {
                    CarouselArrangeItemHidden(activeIndex - i, z, animated);
                }
                // affiche les éléments de droite
                if (i <= n2)
                {
                    z--;
                    if (i <= k2)
                    {
                        CarouselArrangeItem(activeIndex + i, deltaAngle + i * angleEcart, z, animated);
                    }
                    else
                    {
                        CarouselArrangeItemHidden(activeIndex + i, z, animated);
                    }
                }
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        /*
        protected override Visual GetVisualChild(int index)
        {
            if (index < 0 || index >= Children.Count)
            {
                throw new Exception("Invalid Child index: " + index);
            }
            //this.visu
            int zIndex = Panel.GetZIndex(InternalChildren[index]);
            Debug.Print(index.ToString() + " : " + zIndex.ToString());
            
            
            foreach (UIElement child in InternalChildren)
            {
                if (Panel.GetZIndex(child) == InternalChildren.Count - index - 1)
                {
                    return child;
                }
            }
            
            return (Visual)InternalChildren[index];
        }
        */
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void CarouselArrangeItemHidden(int index, int zindex, bool animated)
        {
            UIElement child = InternalChildren[getBoundedIndex(index)];
            Panel.SetZIndex(child, zindex);
            SetTransform(child, 0, 0, 1, animated);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void CarouselArrangeItem(int index, double angle, int zindex, bool animated)
        {
            double x = 0;
            double y = 0;
            double w = 0;
            double h = 0;
            double scale = 1;
            UIElement child = InternalChildren[getBoundedIndex(index)];
            Panel.SetZIndex(child, zindex);

            if (_isVertical)
            {
                w = radius * (1 + Math.Cos(angle)) * _viewport.Width / (2 * radius);
                y = maxSize / 2 + radius * Math.Sin(angle) - child.DesiredSize.Height / 2;
                x = _viewport.Width / 2 - child.DesiredSize.Width / 2;
                scale = w / child.DesiredSize.Width;
                h = scale * child.DesiredSize.Height;
            }
            else
            {
                h = radius * (1 + Math.Cos(angle)) * _viewport.Height / (2 * radius);
                x = maxSize / 2 + radius * Math.Sin(angle) - child.DesiredSize.Width / 2;
                y = _viewport.Height / 2 - child.DesiredSize.Height / 2;
                scale = h / child.DesiredSize.Height;
                w = scale * child.DesiredSize.Width;
            }

            if (index == activeIndex && ScrollOwner != null)
            {
                SetTransform(child, x, y, scale, animated, delegate
                {
                    ScrollTo(angleOffset);
                });
            }
            else
            {
                scale /= CenterScale;
                SetTransform(child, x, y, scale, animated);
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void ScrollTo(double angle)
        {
            double offset = angle * radius + maxSize / 2;
            if (ScrollOwner != null)
            {
                if (_isVertical)
                {
                    ScrollOwner.ScrollToVerticalOffset(offset);
                }
                else
                {
                    ScrollOwner.ScrollToHorizontalOffset(offset);
                }
            }
            else
            {
                CarouselArrange();
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region transform methods
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void SetTransform(UIElement child, double X, double Y, double Scale)
        {
            SetTransform(child, X, Y, Scale, false, null);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void SetTransform(UIElement child, double X, double Y, double Scale, bool animated)
        {
            SetTransform(child, X, Y, Scale, animated, null);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected void SetTransform(UIElement child, double X, double Y, double Scale, bool animated, EventHandler endEvent)
        {
            TransformGroup group = child.RenderTransform as TransformGroup;
            ScaleTransform scale;
            TranslateTransform trans;
            if (animated && group != null)
            {
                scale = group.Children[0] as ScaleTransform;
                trans = group.Children[1] as TranslateTransform;

                if (endEvent != null)
                {
                    scale.BeginAnimation(ScaleTransform.ScaleXProperty, NewAnimation(scale.ScaleX, Scale, CenterScaleDuration, endEvent));
                }
                else
                {
                    scale.BeginAnimation(ScaleTransform.ScaleXProperty, NewAnimation(scale.ScaleX, Scale, CenterScaleDuration));
                }
                scale.BeginAnimation(ScaleTransform.ScaleYProperty, NewAnimation(scale.ScaleY, Scale, CenterScaleDuration));
                trans.BeginAnimation(TranslateTransform.XProperty, NewAnimation(trans.X, X, CenterScaleDuration));
                trans.BeginAnimation(TranslateTransform.YProperty, NewAnimation(trans.Y, Y, CenterScaleDuration));
            }
            else
            {
                group = new TransformGroup();
                scale = new ScaleTransform(Scale, Scale, child.DesiredSize.Width / 2, child.DesiredSize.Height / 2);
                trans = new TranslateTransform(X, Y);
                group.Children.Add(scale);
                group.Children.Add(trans);
                child.RenderTransform = group;
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected DoubleAnimation NewAnimation(double from, double to, double duration)
        {
            return NewAnimation(from, to, duration, null);
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected DoubleAnimation NewAnimation(double from, double to, double duration, EventHandler endEvent)
        {
            DoubleAnimation anim = new DoubleAnimation(from, to, TimeSpan.FromMilliseconds(duration));
            anim.AccelerationRatio = 0.5;
            anim.DecelerationRatio = 0.5;
            if (endEvent != null)
            {
                anim.Completed += endEvent;
            }
            return anim;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region panel Override
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected override Size MeasureOverride(Size availableSize)
        {
            _availableSize = availableSize;

            if (double.IsInfinity(availableSize.Height) || double.IsInfinity(availableSize.Width))
            {
                return new Size(1, 1);
            }

            if (InternalChildren.Count == 0)
            {
                return availableSize;
            }

            _viewport = availableSize;

            if (!initDone)
            {
                activeIndex = InternalChildren.Count - 1;
            }
            else if (InternalChildren.Count > previousCount)
            {
                activeIndex = InternalChildren.Count - 1;
            }
            else if (InternalChildren.Count < previousCount)
            {
                activeIndex = getBoundedIndex(activeIndex - 1);
            }
            angleOffset = getAngleFromIndex(activeIndex);
            if (initDone)
            {
                if (_isVertical)
                {
                    _viewportOffset.Y = getOffsetFromAngle(angleOffset);
                }
                else
                {
                    _viewportOffset.X = getOffsetFromAngle(angleOffset);
                }
            }
            angleEcart = Math.PI / (ItemsCount - 1);
            carouselPadding = 0;

            previousCount = InternalChildren.Count;

            if (isVertical)
            {
                // init
                if (!initDone)
                {
                    _extent = new Size(availableSize.Width, maxSize);
                    _viewportOffset = new Point(0, maxSize / 2);
                    RenderTransform = new TranslateTransform(0, -(_extent.Height - _viewport.Height) / 2.0);
                }

                // measure
                double maxWidth = 0;
                foreach (UIElement child in InternalChildren)
                {
                    child.Measure(availableSize);
                    if (child.DesiredSize.Height > carouselPadding)
                    {
                        carouselPadding = child.DesiredSize.Height;
                    }
                    if (child.DesiredSize.Width > maxWidth)
                    {
                        maxWidth = child.DesiredSize.Width;
                    }
                }
                carouselPadding *= availableSize.Width / (2 * maxWidth);
                radius = (_viewport.Height - carouselPadding) / 2;
            }
            else
            {
                // init
                if (!initDone)
                {
                    _extent = new Size(maxSize, availableSize.Height);
                    _viewportOffset = new Point(maxSize / 2, 0);
                    RenderTransform = new TranslateTransform(-(_extent.Width - _viewport.Width) / 2.0, 0);
                }

                // measure
                double maxHeight = 0;
                foreach (UIElement child in InternalChildren)
                {
                    child.Measure(availableSize);
                    if (child.DesiredSize.Width > carouselPadding)
                    {
                        carouselPadding = child.DesiredSize.Width;
                    }
                    if (child.DesiredSize.Height > maxHeight)
                    {
                        maxHeight = child.DesiredSize.Height;
                    }
                }
                carouselPadding *= availableSize.Height / (2 * maxHeight);
                radius = (_viewport.Width - carouselPadding) / 2;
            }

            initDone = true;

            return availableSize;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected override Size ArrangeOverride(Size finalSize)
        {

            foreach (UIElement child in InternalChildren)
            {
                child.Arrange(new Rect(0, 0, child.DesiredSize.Width, child.DesiredSize.Height));
            }
            this.CarouselArrange();
            if (_ScrollOwner != null)
            {
                _ScrollOwner.InvalidateScrollInfo();
            }
            this.UpdateLayout();
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
            int index = InternalChildren.IndexOf((UIElement)visual);
            int currentIndex = InternalChildren.IndexOf(activeElement);
            if (index != currentIndex)
            {
                angleOffset = getAngleFromIndex(index);
                ScrollTo(angleOffset);
            }
            return rectangle;
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        public void SetHorizontalOffset(double offset)
        {
            if (CanHorizontallyScroll && !_isVertical)
            {
                _viewportOffset.X = offset;
                MoveToOffset(offset);
            }
        }

        public void SetVerticalOffset(double offset)
        {
            if (CanVerticallyScroll && _isVertical)
            {
                _viewportOffset.Y = offset;
                MoveToOffset(offset);
            }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        #endregion

        #region IScrollInfo Membres (partie purement declarative)
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
        protected bool _CanHorizontallyScroll = false;
        public bool CanHorizontallyScroll
        {
            get { return _CanHorizontallyScroll; }
            set { _CanHorizontallyScroll = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected bool _CanVerticallyScroll = false;
        public bool CanVerticallyScroll
        {
            get { return _CanVerticallyScroll; }
            set { _CanVerticallyScroll = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected Size _extent = new Size(0, 0);
        public double ExtentHeight
        {
            get { return _extent.Height; }
        }

        public double ExtentWidth
        {
            get { return _extent.Width; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected ScrollViewer _ScrollOwner;
        public ScrollViewer ScrollOwner
        {
            get { return _ScrollOwner; }
            set { _ScrollOwner = value; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected Point _viewportOffset;
        public double HorizontalOffset
        {
            get { return _viewportOffset.X; }
        }

        public double VerticalOffset
        {
            get { return _viewportOffset.Y; }
        }
        //- - - - - - - - - - - - - - - - - - - - - - - -
        protected Size _viewport = new Size(0, 0);
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

