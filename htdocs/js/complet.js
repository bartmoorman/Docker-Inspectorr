$(document).ready(function() {
  $.get('query.php', {"function": "getStatuses"})
    .done(function(data) {
      statuses = $.parseJSON(data);
      $.each(statuses, function(statusID, status) {
        Complet.addStatus(status);
      });
    });

  Complet.syncStartStatus('#libraries-loading');
  $.getJSON('query.php', {"function": "getLibraries"})
    .done(function(libraries) {
      $.each(libraries, function(libraryID, library) {
        Complet.addLibrary(library);
        $.each(library.details, function(libraryDetailID, libraryDetail) {
          Complet.addLibraryDetail(library, libraryDetail);
        });
      });
      Complet.syncStopStatus('#libraries-loading');
    })
    .fail(function(jqxhr, textStatus, errorThrown) {
      console.log(`getLibraries failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
      Complet.syncErrorStatus('#libraries-loading');
    });

  $(document).on('click', 'span.toggle-status-sections', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    if ($(`#library-${library.id}-status-${status.status}`).is(':hidden')) {
      Complet.sectionOpen(`#library-${library.id}-status-${status.status}`);
      Complet.sectionOpenStatus($(this).children('span.fa'));
      if (!$(`#library-${library.id}-status-${status.status} > div`).not('.card-header').length) {
        Complet.syncStartStatus('#libraries-loading');
        $.getJSON('query.php', {"function": "getLibrarySections", "library": library.id, "status": status.status})
          .done(function(librarySections) {
            if (librarySections.length == 1) {
              $.each(librarySections, function(librarySectionID, librarySection) {
                Complet.addLibrarySection(library, status, librarySection);
                Complet.addLibrarySectionDetails(library, status, librarySection);
                Complet.sectionOpenStatus($(`#library-${library.id}-status-${status.status}-section-${librarySection.id}-summary > span.badge > span.fa`));
                $.getJSON('query.php', {"function": "getLibrarySectionDetails", "library": library.id, "status": status.status, "section": librarySection.id})
                  .done(function(librarySectionDetails) {
                    $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
                      Complet.addLibrarySectionDetail(library, status, librarySection, librarySectionDetail);
                    });
                  })
                  .fail(function(jqxhr, textStatus, errorThrown) {
                    console.log(`getLibrarySectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                    Complet.syncErrorStatus('#libraries-loading');
                  });
              });
            } else {
              $.each(librarySections, function(librarySectionID, librarySection) {
                Complet.addLibrarySection(library, status, librarySection);
              });
            }
            Complet.syncStopStatus('#libraries-loading');
          })
          .fail(function(jqxhr, textStatus, errorThrown) {
            console.log(`getLibrarySections failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
            Complet.syncErrorStatus('#libraries-loading');
          });
      }
    } else if ($(`#library-${library.id}-status-${status.status}`).is(':visible')) {
      Complet.sectionClose(`#library-${library.id}-status-${status.status}`);
      Complet.sectionCloseStatus($(this).children('span.fa'));
    }
  });

  $(document).on('click', 'span.toggle-status-section-details', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    var section = $(this).data('section');
    if (!$(`#library-${library.id}-status-${status.status}-section-${section.id}-details`).length) {
      Complet.addLibrarySectionDetails(library, status, section);
      Complet.sectionOpenStatus($(this).children('span.fa'));
      Complet.syncStartStatus('#libraries-loading');
      $.getJSON('query.php', {"function": "getLibrarySectionDetails", "library": library.id, "status": status.status, "section": section.id})
        .done(function(librarySectionDetails) {
          $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
            Complet.addLibrarySectionDetail(library, status, section, librarySectionDetail);
          });
          Complet.syncStopStatus('#libraries-loading');
        })
        .fail(function(jqxhr, textStatus, errorThrown) {
          console.log(`getLibrarySectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
          Complet.syncErrorStatus('#libraries-loading');
        });
    } else if ($(`#library-${library.id}-status-${status.status}-section-${section.id}-details`).is(':hidden')) {
      Complet.sectionOpen(`#library-${library.id}-status-${status.status}-section-${section.id}-details`);
      Complet.sectionOpenStatus($(this).children('span.fa'));
    } else if ($(`#library-${library.id}-status-${status.status}-section-${section.id}-details`).is(':visible')) {
      Complet.sectionClose(`#library-${library.id}-status-${status.status}-section-${section.id}-details`);
      Complet.sectionCloseStatus($(this).children('span.fa'));
    }
  });

  $(document).on('click', 'span.refresh-libraries', function() {
    var visibleStatuses = [];
    $('span.toggle-status-sections').each(function() {
      var library = $(this).data('library');
      var status = $(this).data('status');
      visibleStatuses[library.id] = visibleStatuses[library.id] || [];
      if ($(`#library-${library.id}-status-${status.status}`).is(':visible')) {
        visibleStatuses[library.id][status.status] = [];
        $(`#library-${library.id}-status-${status.status} span.toggle-status-section-details`).each(function() {
          var library = $(this).data('library');
          var status = $(this).data('status');
          var section = $(this).data('section');
          if ($(`#library-${library.id}-status-${status.status}-section-${section.id}-details`).is(':visible')) {
            visibleStatuses[library.id][status.status].push(section.id);
          }
        });
      }
    });
    Complet.syncStartStatus('#libraries-loading');
    $.getJSON('query.php', {"function": "getLibraries"})
      .done(function(libraries) {
        Complet.removeAllLibraries();
        $.each(libraries, function(libraryID, library) {
          Complet.addLibrary(library);
          $.each(library.details, function(libraryDetailID, libraryDetail) {
            Complet.addLibraryDetail(library, libraryDetail);
            if (visibleStatuses[library.id][libraryDetail.status]) {
              Complet.sectionOpen(`#library-${library.id}-status-${libraryDetail.status}`);
              Complet.sectionOpenStatus(`#library-${library.id}-summary > h4 > span.badge-${statuses[libraryDetail.status].class} > span.fa`);
              $.getJSON('query.php', {"function": "getLibrarySections", "library": library.id, "status": libraryDetail.status})
                .done(function(librarySections) {
                  $.each(librarySections, function(librarySectionID, librarySection) {
                    Complet.addLibrarySection(library, libraryDetail, librarySection);
                    if (visibleStatuses[library.id][libraryDetail.status].includes(librarySection.id)) {

                      Complet.addLibrarySectionDetails(library, libraryDetail, librarySection);
                      Complet.sectionOpenStatus(`#library-${library.id}-status-${libraryDetail.status}-section-${librarySection.id}-summary > span.badge > span.fa`);
                      $.getJSON('query.php', {"function": "getLibrarySectionDetails", "library": library.id, "status": libraryDetail.status, "section": librarySection.id})
                        .done(function(librarySectionDetails) {
                          $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
                            Complet.addLibrarySectionDetail(library, libraryDetail, librarySection, librarySectionDetail);
                          });
                        })
                        .fail(function(jqxhr, textStatus, errorThrown) {
                          console.log(`getLibrarySectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                          Complet.syncErrorStatus('#libraries-loading');
                        });

                    };
                  });
                })
                .fail(function(jqxhr, textStatus, errorThrown) {
                  console.log(`getLibrarySections failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                  Complet.syncErrorStatus('#libraries-loading');
                });
            };
          });
        });
        Complet.syncStopStatus('#libraries-loading');
      })
      .fail(function(jqxhr, textStatus, errorThrown) {
        console.log(`getLibraries failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
        Complet.syncErrorStatus('#libraries-loading');
      });
  });
});
