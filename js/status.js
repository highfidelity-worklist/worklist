$(function() {
    // Collect Bidding Jobs info
    getBiddingReviewDrawers();    
    window.scrollTo(0, $('#entries').outerHeight());
});



function getBiddingReviewDrawers() {
    doc = document.location.href.split("journal");
    $.ajax({
        url:'api.php',
        type: 'post',
        data: { 'action':'getSystemDrawerJobs'  },
        dataType: 'json',
        success: function(json) {
            fillBiddingReviewDrawers(json);
        }
    });
}

function fillBiddingReviewDrawers(json) {
    if (json == null || !json.success) {
        return;
    }
    var bidding = (json.bidding == 0 || json.bidding == null) ? 'no jobs' : (json.bidding == 1 ? '1 job' : json.bidding + ' jobs');
    var review = (json.review == 0 || json.review == null) ? 'no jobs' : (json.review == 1 ? '1 job' : json.review + ' jobs');
    
    $('#need-review ul li').remove();
    $('#need-review ul + a').remove();
    if (parseInt(review) > 0 && json.need_review) {
        var need_review = json.need_review;
        for (var i = 0; i < need_review.length; i++) {
            workitem = need_review[i];
            var li = $('<li>');
            $('<span>#' + workitem.id + '</span> ').appendTo(li);
            $('<a>').attr(
                {
                    class: 'workitem-' + workitem.id,
                    href: './' + workitem.id
                }).text(workitem.summary).appendTo(li);
            $('#need-review ul').append(li);
        }
        if (parseInt(review) > 7) {
            $('<a>').attr({href: './jobs?project=&user=0&status=needs-review'})
                .text('View them all')
                .insertAfter('#need-review ul');
        }
        $('#need-review ul').show();
    }  else {
        $('#need-review ul').hide();
    }
    $('#biddingJobs p > span + a').text(bidding);
    $('#biddingJobs p > span').text(parseInt(bidding) == 1 ? 'is' : 'are');
}
