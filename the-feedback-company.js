function feedbackcompany_init()
{
	feedbackcompany_reviewslides = new Array();
	feedbackcompany_reviewslidescontent = jQuery('.feedbackcompany-reviewcontent');

	// lange reviews truncaten
	for (var i = 0; i < feedbackcompany_reviewslidescontent.length; i++)
	{
		maxheight = jQuery(feedbackcompany_reviewslidescontent[i]).height() + 10;
		curheight = feedbackcompany_reviewslidescontent[i].scrollHeight;

		while (curheight > maxheight)
		{
			feedbackcompany_reviewslidescontent[i].innerHTML = feedbackcompany_reviewslidescontent[i].innerHTML.replace(/\W*\s(\S)*$/, '...');
			curheight = feedbackcompany_reviewslidescontent[i].scrollHeight;
		}
	}

	// alle reviews verbergen
	jQuery('div[id^="feedbackcompany-reviewslider"]').each(function() {
		feedbackcompany_reviewslides.push(this);
		jQuery(this).hide();
	});

	// eerstvolgende review weergeven
	if (feedbackcompany_reviewslides.length > 0)
	{
		feedbackcompany_slidenext();
		setInterval(feedbackcompany_slidenext, 3000);
	}
}

function feedbackcompany_slidenext()
{
	// don't slide next if hovered
	if (jQuery('div[id^="feedbackcompany-reviewslider"]:hover').length > 0)
		return;

	var i;
	for (i = 0; i < feedbackcompany_reviewslides.length; i++)
	{
		if (jQuery(feedbackcompany_reviewslides[i]).is(":visible"))
			break;
	}

	if (feedbackcompany_reviewslides[i] !== undefined)
		jQuery(feedbackcompany_reviewslides[i]).fadeOut(100);

	i++;

	if (feedbackcompany_reviewslides[i] === undefined)
		i = 0;

	if (feedbackcompany_reviewslides[i] !== undefined)
		jQuery(feedbackcompany_reviewslides[i]).delay(200).fadeIn(100);
}

jQuery(document).ready(feedbackcompany_init);
