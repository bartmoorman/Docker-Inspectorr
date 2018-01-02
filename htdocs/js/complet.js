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

  $(document).on('click', '.toggle-library-status-sections', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    if ($(`#library-${library.id}-${status.status}-sections`).is(':hidden')) {
      Complet.sectionOpen(`#library-${library.id}-${status.status}-sections`);
      Complet.sectionOpenStatus($(this).children('.library-status-sections-collapse-icon'));
      if (!$(`#library-${library.id}-${status.status}-sections`).children('.library-status-section').length) {
        Complet.syncStartStatus('#libraries-loading');
        $.getJSON('query.php', {"function": "getLibrarySections", "library": library.id, "status": status.status})
          .done(function(librarySections) {
            if (librarySections.length == 1) {
              $.each(librarySections, function(librarySectionID, librarySection) {
                Complet.addLibrarySection(library, status, librarySection);
                Complet.addLibrarySectionDetails(library, status, librarySection);
                Complet.sectionOpenStatus($(`#library-${library.id}-${status.status}-section-${librarySection.id}-summary`).children('.library-status-section-details-collapse-icon'));
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
    } else if ($(`#library-${library.id}-${status.status}-sections`).is(':visible')) {
      Complet.sectionClose(`#library-${library.id}-${status.status}-sections`);
      Complet.sectionCloseStatus($(this).children('.library-status-sections-collapse-icon'));
    }
  });

  $(document).on('click', '.toggle-library-status-section-details', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    var section = $(this).data('section');
    if (!$(`#library-${library.id}-${status.status}-section-${section.id}-details`).length) {
      Complet.syncStartStatus('#libraries-loading');
      Complet.addLibrarySectionDetails(library, status, section);
      Complet.sectionOpenStatus($(this).children('.library-status-section-details-collapse-icon'));
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
    } else if ($(`#library-${library.id}-${status.status}-section-${section.id}-details`).is(':hidden')) {
      Complet.sectionOpen(`#library-${library.id}-${status.status}-section-${section.id}-details`);
      Complet.sectionOpenStatus($(this).children('.library-status-section-details-collapse-icon'));
    } else if ($(`#library-${library.id}-${status.status}-section-${section.id}-details`).is(':visible')) {
      Complet.sectionClose(`#library-${library.id}-${status.status}-section-${section.id}-details`);
      Complet.sectionCloseStatus($(this).children('.library-status-section-details-collapse-icon'));
    }
  });

  $(document).on('click', '.refresh-library', function() {
    var library = $(this).data('library');
    Complet.syncStartStatus('#libraries-loading');
    $.getJSON('query.php', {"function": "getLibraryDetails", "library": library.id})
      .done(function(libraryDetails) {
        var visibleSections = [];
        $(`.library-${library.id}-section.card:visible`).each(function() {
          var visibleSection = [];
          visibleSection.details = [];
          visibleSection.library = $(this).data('library');
          visibleSection.status = $(this).data('status');
          $(this).children('.library-status-section').children('.card-body:visible').each(function() {
            var visibleSectionDetails = [];
            visibleSectionDetails.section = $(this).data('section')
            visibleSection.details.push(visibleSectionDetails);
          });
          visibleSections.push(visibleSection);
        });
        Complet.removeLibraryDetail(library);
        $.each(libraryDetails, function(libraryDetailID, libraryDetail) {
          Complet.addLibraryDetail(library, libraryDetail);
        });
        $.each(visibleSections, function(visibleSectionID, visibleSection) {
          if ($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-sections`).length) {
            Complet.sectionOpen(`#library-${visibleSection.library.id}-${visibleSection.status.status}-sections`);
            Complet.sectionOpenStatus($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-summary`).children('.library-status-sections-collapse-icon'));
            $.getJSON('query.php', {"function": "getLibrarySections", "library": visibleSection.library.id, "status": visibleSection.status.status})
              .done(function(librarySections) {
                $.each(librarySections, function(librarySectionID, librarySection) {
                  Complet.addLibrarySection(visibleSection.library, visibleSection.status, librarySection);
                });
                $.each(visibleSection.details, function(visibleSectionDetailID, visibleSectionDetail) {
                  if ($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-section-${visibleSectionDetail.section.id}`).length) {
                    Complet.addLibrarySectionDetails(visibleSection.library, visibleSection.status, visibleSectionDetail.section);
                    Complet.sectionOpenStatus($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-section-${visibleSectionDetail.section.id}-summary`).children('.library-status-section-details-collapse-icon'));
                    $.getJSON('query.php', {"function": "getLibrarySectionDetails", "library": visibleSection.library.id, "status": visibleSection.status.status, "section": visibleSectionDetail.section.id})
                      .done(function(librarySectionDetails) {
                        $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
                          Complet.addLibrarySectionDetail(visibleSection.library, visibleSection.status, visibleSectionDetail.section, librarySectionDetail);
                        });
                      })
                      .fail(function(jqxhr, textStatus, errorThrown) {
                        console.log(`getLibrarySectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                        Complet.syncErrorStatus('#libraries-loading');
                      });
                  }
                });
              })
              .fail(function(jqxhr, textStatus, errorThrown) {
                console.log(`getLibrarySections failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                Complet.syncErrorStatus('#libraries-loading');
              });
          }
        });
        Complet.syncStopStatus('#libraries-loading');
      })
      .fail(function(jqxhr, textStatus, errorThrown) {
        console.log(`getLibraryDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
        Complet.syncErrorStatus('#libraries-loading');
      });
  });
});
