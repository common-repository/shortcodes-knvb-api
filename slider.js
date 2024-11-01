jQuery(function() {
  jQuery(".knvbteam-slider").each(function() {
    var sliderElt = jQuery(this);
    jQuery(".game", sliderElt).hide();
    jQuery(".game", sliderElt).first().show();

    sliderElt.prepend("<a class=\"prev\" href title=\"Vorige wedstrijd\">Vorige</a>");
    jQuery("a.prev", sliderElt).off("click").on("click", function(event) {
      var current = jQuery(".game:visible", sliderElt);
      var next = jQuery(".game:visible", sliderElt).next(".game");

      if(next.length) {
        current.hide();
        next.show();
      }

      checkButtonState(sliderElt);
      event.preventDefault();
    });

    sliderElt.append("<a class=\"next\" href title=\"Volgende wedstrijd\">Volgende</a>");
    jQuery("a.next", sliderElt).off("click").on("click", function(event) {
      var current = jQuery(".game:visible", sliderElt);
      var prev = jQuery(".game:visible", sliderElt).prev(".game");

      if(prev.length) {
        current.hide();
        prev.show();
      }

      checkButtonState(sliderElt);
      event.preventDefault();
    });

    checkButtonState(sliderElt);
  });
});

function checkButtonState(sliderElt) {
  if(jQuery(".game:visible", sliderElt).next(".game").length) {
    jQuery("a.prev", sliderElt).show();
  }
  else {
    jQuery("a.prev", sliderElt).hide();
  }

  if(jQuery(".game:visible", sliderElt).prev(".game").length) {
    jQuery("a.next", sliderElt).show();
  }
  else {
    jQuery("a.next", sliderElt).hide();
  }
}
