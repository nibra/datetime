<?php
/**
 * Part of the Joomla Framework DateTime Package
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 2.1 or later; see LICENSE
 */

namespace Joomla\DateTime;

/**
 * Class representing a range of a time. You can create a range from start to end date,
 * but also from start date or end date for the given number of dates.
 *
 * @since  2.0.0
 */
final class DateTimeRange implements \IteratorAggregate
{
	/**
	 * DateTime object representing the start date of the iterator
	 *
	 * @var    DateTime
	 * @since  2.0.0
	 */
	protected $start;

	/**
	 * DateTime object representing the end date of the iterator
	 *
	 * @var    DateTime
	 * @since  2.0.0
	 */
	protected $end;

	/**
	 * Interval between dates
	 *
	 * @var    DateInterval
	 * @since  2.0.0
	 */
	protected $interval;

	/**
	 * Constructor.
	 *
	 * @param   DateTime      $start     The start date.
	 * @param   DateTime      $end       The end date.
	 * @param   DateInterval  $interval  The interval between adjacent dates.
	 *
	 * @since   2.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function __construct(DateTime $start, DateTime $end, DateInterval $interval)
	{
		if ($start->isBefore($end) && $start->add($interval)->isAfter($end))
		{
			throw new \InvalidArgumentException("Interval is too big");
		}

		$this->start = $start;
		$this->end = $end;
		$this->interval = $interval;
	}

	/**
	 * Creates a DateTimeRange object from the start date for the given amount od dates.
	 *
	 * @param   DateTime      $start     The start date.
	 * @param   integer       $amount    The amount of dates included in a range.
	 * @param   DateInterval  $interval  The interval between adjacent dates.
	 *
	 * @return  DateTimeRange
	 *
	 * @since   2.0.0
	 */
	public static function from(DateTime $start, $amount, DateInterval $interval)
	{
		$end = self::buildDatetime($start, $amount, $interval, true);

		return new DateTimeRange($start, $end, $interval);
	}

	/**
	 * Creates a DateTimeRange object to the end date for the given amount od dates.
	 *
	 * @param   DateTime      $end       The end date.
	 * @param   integer       $amount    The amount of dates included in a range.
	 * @param   DateInterval  $interval  The interval between adjacent dates.
	 *
	 * @return  DateTimeRange
	 *
	 * @since   2.0.0
	 */
	public static function to(DateTime $end, $amount, DateInterval $interval)
	{
		$start = self::buildDatetime($end, $amount, $interval, false);

		return new DateTimeRange($start, $end, $interval);
	}

	/**
	 * Returns an empty range.
	 *
	 * @return  DateTimeRange
	 *
	 * @since   2.0.0
	 */
	public static function emptyRange()
	{
		return new DateTimeRange(DateTime::tomorrow(), DateTime::yesterday(), new DateInterval('P1D'));
	}

	/**
	 * Returns the start date.
	 *
	 * @return  DateTime
	 *
	 * @since   2.0.0
	 */
	public function start()
	{
		return $this->start;
	}

	/**
	 * Returns the end date.
	 *
	 * @return  DateTime
	 *
	 * @since   2.0.0
	 */
	public function end()
	{
		return $this->end;
	}

	/**
	 * Checks if a range is empty.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public function isEmpty()
	{
		return $this->start->isAfter($this->end);
	}

	/**
	 * Checks if the given date is included in the range.
	 *
	 * @param   DateTime  $datetime  The date to compare to.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public function includes(DateTime $datetime)
	{
		return !$datetime->isBefore($this->start) && !$datetime->isAfter($this->end);
	}

	/**
	 * Checks if ranges are equal.
	 *
	 * @param   DateTimeRange  $range  The range to compare to.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public function equals(DateTimeRange $range)
	{
		return $this->start->equals($range->start) && $this->end->equals($range->end) && $this->interval->equals($range->interval);
	}

	/**
	 * Checks if ranges overlap with each other.
	 *
	 * @param   DateTimeRange  $range  The range to compare to.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public function overlaps(DateTimeRange $range)
	{
		return $range->includes($this->start) || $range->includes($this->end) || $this->includesRange($range);
	}

	/**
	 * Checks if the given range is included in the current one.
	 *
	 * @param   DateTimeRange  $range  The range to compare to.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public function includesRange(DateTimeRange $range)
	{
		return $this->includes($range->start) && $this->includes($range->end);
	}

	/**
	 * Returns a gap range between two ranges.
	 *
	 * @param   DateTimeRange  $range  The range to compare to.
	 *
	 * @return  DateTimeRange
	 *
	 * @since   2.0.0
	 */
	public function gap(DateTimeRange $range)
	{
		if ($this->overlaps($range))
		{
			return self::emptyRange();
		}

		if ($this->start->isBefore($range->start))
		{
			$lower = $this;
			$higher = $range;
		}
		else
		{
			$lower = $range;
			$higher = $this;
		}

		return new DateTimeRange($lower->end->add($this->interval), $higher->start->sub($this->interval), $this->interval);
	}

	/**
	 * Checks if ranges abuts with each other.
	 *
	 * @param   DateTimeRange  $range  The range to compare to.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public function abuts(DateTimeRange $range)
	{
		return !$this->overlaps($range) && $this->gap($range)->isEmpty();
	}

	/**
	 * Returns an array of dates which are included in the current range.
	 *
	 * @return  DateTime[]
	 *
	 * @since   2.0.0
	 */
	public function toArray()
	{
		$range = array();

		foreach ($this as $datetime)
		{
			$range[] = $datetime;
		}

		return $range;
	}

	/**
	 * Returns an external iterator.
	 *
	 * @return  DateTimeIterator
	 *
	 * @since   2.0.0
	 */
	public function getIterator()
	{
		return new DateTimeIterator($this->start, $this->end, $this->interval);
	}

	/**
	 * Returns string representation of the range.
	 *
	 * @return  string
	 *
	 * @since   2.0.0
	 */
	public function __toString()
	{
		return sprintf('%s - %s', $this->start->format('Y-m-d H:i:s'), $this->end->format('Y-m-d H:i:s'));
	}

	/**
	 * Returns a range which is created by combination of given ranges. There can't
	 * be a gap between given ranges.
	 *
	 * @param   DateTimeRange[]  $ranges  An array of ranges to combine.
	 *
	 * @return  DateTimeRange
	 *
	 * @since   2.0.0
	 * @throws  \InvalidArgumentException
	 */
	public static function combination(array $ranges)
	{
		if (!self::isContiguous($ranges))
		{
			throw new \InvalidArgumentException('Unable to combine date ranges');
		}

		return new DateTimeRange($ranges[0]->start, $ranges[count($ranges) - 1]->end, $ranges[0]->interval);
	}

	/**
	 * Checks if ranges are contiguous.
	 *
	 * @param   DateTimeRange[]  $ranges  An array of ranges to combine.
	 *
	 * @return  boolean
	 *
	 * @since   2.0.0
	 */
	public static function isContiguous(array $ranges)
	{
		$ranges = self::sortArrayOfRanges($ranges);

		for ($i = 0; $i < count($ranges) - 1; $i++)
		{
			if (!$ranges[$i]->abuts($ranges[$i + 1]))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Sorts an array of ranges.
	 *
	 * @param   DateTimeRange[]  $ranges  An array of ranges to sort.
	 *
	 * @return  DateTimeRange[]
	 *
	 * @since   2.0.0
	 */
	protected static function sortArrayOfRanges(array $ranges)
	{
		usort($ranges, array("self", "compare"));

		return array_values($ranges);
	}

	/**
	 * Compares two objects.
	 *
	 * @param   DateTimeRange  $a  Base object.
	 * @param   DateTimeRange  $b  Object to compare to.
	 *
	 * @return  integer
	 *
	 * @since   2.0.0
	 * @throws  \InvalidArgumentException
	 */
	private static function compare(DateTimeRange $a, DateTimeRange $b)
	{
		if (!$a->interval->equals($b->interval))
		{
			throw new \InvalidArgumentException('Intervals of ranges are not equal.');
		}

		if ($a->equals($b))
		{
			return 0;
		}

		if ($a->start()->isAfter($b->start()))
		{
			return 1;
		}

		if ($a->start()->isBefore($b->start()) || $a->end()->isBefore($b->end()))
		{
			return -1;
		}

		return 1;
	}

	/**
	 * Builds the date.
	 *
	 * @param   DateTime      $base        The base date.
	 * @param   integer       $amount      The amount of dates included in a range.
	 * @param   DateInterval  $interval    The interval between adjacent dates.
	 * @param   boolean       $byAddition  Should build the final date using addition or subtraction?
	 *
	 * @return  DateTime
	 *
	 * @since   2.0.0
	 * @throws  \InvalidArgumentException
	 */
	private static function buildDatetime(DateTime $base, $amount, DateInterval $interval, $byAddition = true)
	{
		if (intval($amount) < 2)
		{
			throw new \InvalidArgumentException('Amount have to be greater than 2');
		}

		// Start from 2, because start date and end date also count
		for ($i = 2; $i <= $amount; $i++)
		{
			$base = $byAddition ? $base->add($interval) : $base->sub($interval);
		}

		return $base;
	}
}
