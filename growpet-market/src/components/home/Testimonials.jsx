import { useEffect, useRef, useState } from "react";

import { formatWeight } from "../../data/pets";
import { fetchTestimonials } from "../../features/testimonials/testimonials.api";
import Alert from "../ui/Alert";

const TESTIMONIAL_ITEM_PREVIEW_LIMIT = 1;

function formatDate(value) {
  if (!value) {
    return "";
  }

  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
  }).format(new Date(value));
}

function getItemDisplay(item) {
  if (item.type === "token") {
    return {
      title: item.name,
      detail:
        item.packageLabel ||
        `${item.tokenAmount ? `${item.tokenAmount} token` : "Token order"}`,
      quantity: item.quantity,
    };
  }

  const details = [
    item.mutation,
    item.weightKg ? formatWeight(item.weightKg) : null,
  ]
    .filter(Boolean)
    .join(" / ");

  return {
    title: item.name,
    detail: details || "Pet order",
    quantity: item.quantity,
  };
}

function Testimonials() {
  const [testimonials, setTestimonials] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedProof, setSelectedProof] = useState(null);
  const [activeTestimonialIndex, setActiveTestimonialIndex] = useState(0);
  const testimonialCarouselRef = useRef(null);
  const [expandedTestimonials, setExpandedTestimonials] = useState(
    () => new Set(),
  );

  useEffect(() => {
    let isMounted = true;

    async function loadTestimonials() {
      try {
        const nextTestimonials = await fetchTestimonials();

        if (isMounted) {
          setTestimonials(nextTestimonials);
        }
      } catch (requestError) {
        if (isMounted) {
          setError(requestError.message);
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    loadTestimonials();

    return () => {
      isMounted = false;
    };
  }, []);

  useEffect(() => {
    if (!selectedProof) {
      return undefined;
    }

    function handleKeyDown(event) {
      if (event.key === "Escape") {
        setSelectedProof(null);
      }
    }

    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [selectedProof]);

  function scrollToTestimonial(index) {
    if (!testimonials.length) {
      return;
    }

    const nextIndex = (index + testimonials.length) % testimonials.length;
    const nextSlide = testimonialCarouselRef.current?.querySelector(
      `[data-testimonial-index="${nextIndex}"]`,
    );

    setActiveTestimonialIndex(nextIndex);
    nextSlide?.scrollIntoView({
      behavior: "smooth",
      block: "nearest",
      inline: "center",
    });
  }

  function syncActiveTestimonial() {
    const carousel = testimonialCarouselRef.current;

    if (!carousel) {
      return;
    }

    const slides = Array.from(
      carousel.querySelectorAll("[data-testimonial-index]"),
    );
    const carouselCenter = carousel.scrollLeft + carousel.clientWidth / 2;
    const closestSlide = slides.reduce((closest, slide) => {
      const slideCenter = slide.offsetLeft + slide.clientWidth / 2;
      const distance = Math.abs(carouselCenter - slideCenter);

      if (!closest || distance < closest.distance) {
        return { index: Number(slide.dataset.testimonialIndex), distance };
      }

      return closest;
    }, null);

    if (closestSlide) {
      setActiveTestimonialIndex(closestSlide.index);
    }
  }

  function toggleTestimonialItems(testimonialId) {
    setExpandedTestimonials((currentItems) => {
      const nextItems = new Set(currentItems);

      if (nextItems.has(testimonialId)) {
        nextItems.delete(testimonialId);
      } else {
        nextItems.add(testimonialId);
      }

      return nextItems;
    });
  }

  return (
    <section className="home-section">
      {isLoading && (
        <section className="empty-state">
          <h3>Memuat testimoni</h3>
          <p>Mengambil bukti trade dari order yang sudah selesai.</p>
        </section>
      )}

      {!isLoading && error && (
        <section className="empty-state">
          <Alert tone="error" title="Testimoni gagal dimuat">
            {error}
          </Alert>
        </section>
      )}

      {!isLoading && !error && testimonials.length === 0 && (
        <section className="empty-state">
          <h3>Belum ada testimoni</h3>
          <p>Bukti trade dari order delivered akan tampil otomatis di sini.</p>
        </section>
      )}

      {!isLoading && !error && testimonials.length > 0 && (
        <div className="testimonial-carousel">
          <div
            className="testimonial-grid"
            ref={testimonialCarouselRef}
            onScroll={syncActiveTestimonial}
          >
            {testimonials.map((testimonial, testimonialIndex) => {
              const isExpanded = expandedTestimonials.has(testimonial.id);
              const hasMoreItems =
                testimonial.items.length > TESTIMONIAL_ITEM_PREVIEW_LIMIT;
              const visibleItems = isExpanded
                ? testimonial.items
                : testimonial.items.slice(0, TESTIMONIAL_ITEM_PREVIEW_LIMIT);
              const hiddenItemCount =
                testimonial.items.length - TESTIMONIAL_ITEM_PREVIEW_LIMIT;

              return (
                <article
                  className="testimonial-card"
                  data-testimonial-index={testimonialIndex}
                  key={testimonial.id}
                >
                  <button
                    className="testimonial-proof"
                    type="button"
                    onClick={() =>
                      setSelectedProof({
                        url: testimonial.deliveryProof.url,
                        robloxUsername: testimonial.robloxUsername,
                      })
                    }
                  >
                    <img
                      src={testimonial.deliveryProof.url}
                      alt={`Bukti trade ${testimonial.robloxUsername}`}
                    />
                  </button>

                  <div className="testimonial-card__body">
                    <div className="testimonial-info-card testimonial-buyer">
                      <div>
                        <span>Username Roblox</span>
                        <strong>{testimonial.robloxUsername}</strong>
                      </div>
                      {testimonial.deliveryProof.uploadedAt && (
                        <small>{formatDate(testimonial.deliveryProof.uploadedAt)}</small>
                      )}
                    </div>
                    <div className="testimonial-info-card testimonial-items">
                      <div className="testimonial-items__head">
                        <span>Item dibeli</span>
                        {hasMoreItems && (
                          <button
                            className="testimonial-items__toggle"
                            type="button"
                            aria-expanded={isExpanded}
                            onClick={() => toggleTestimonialItems(testimonial.id)}
                          >
                            {isExpanded
                              ? "Sembunyikan"
                              : `Lihat semua +${hiddenItemCount}`}
                          </button>
                        )}
                      </div>
                      <div className="testimonial-items__list">
                        {visibleItems.map((item) => {
                          const itemDisplay = getItemDisplay(item);

                          return (
                            <div className="testimonial-item-chip" key={item.id}>
                              <p>
                                <strong>{itemDisplay.title}</strong>
                                <small>
                                  {itemDisplay.detail} / Qty {itemDisplay.quantity}
                                </small>
                              </p>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  </div>
                </article>
              );
            })}
          </div>

          {testimonials.length > 1 && (
            <div className="testimonial-carousel__controls">
              <button
                className="testimonial-carousel__arrow"
                type="button"
                aria-label="Testimoni sebelumnya"
                onClick={() => scrollToTestimonial(activeTestimonialIndex - 1)}
              >
                <span aria-hidden="true">{"<"}</span>
              </button>

              <div className="testimonial-carousel__dots" aria-label="Pilih testimoni">
                {testimonials.map((testimonial, testimonialIndex) => (
                  <button
                    className="testimonial-carousel__dot"
                    type="button"
                    aria-current={
                      testimonialIndex === activeTestimonialIndex ? "true" : undefined
                    }
                    aria-label={`Tampilkan testimoni ${testimonialIndex + 1}`}
                    key={testimonial.id}
                    onClick={() => scrollToTestimonial(testimonialIndex)}
                  />
                ))}
              </div>

              <button
                className="testimonial-carousel__arrow"
                type="button"
                aria-label="Testimoni berikutnya"
                onClick={() => scrollToTestimonial(activeTestimonialIndex + 1)}
              >
                <span aria-hidden="true">{">"}</span>
              </button>
            </div>
          )}
        </div>
      )}

      {selectedProof && (
        <div
          className="testimonial-proof-modal"
          role="dialog"
          aria-modal="true"
          aria-label={`Bukti trade ${selectedProof.robloxUsername}`}
        >
          <button
            className="testimonial-proof-modal__backdrop"
            type="button"
            aria-label="Tutup preview bukti trade"
            onClick={() => setSelectedProof(null)}
          />
          <div className="testimonial-proof-modal__panel">
            <div className="testimonial-proof-modal__head">
              <div>
                <span>Bukti trade</span>
                <strong>{selectedProof.robloxUsername}</strong>
              </div>
              <button type="button" onClick={() => setSelectedProof(null)}>
                Tutup
              </button>
            </div>
            <img
              src={selectedProof.url}
              alt={`Bukti trade ${selectedProof.robloxUsername}`}
            />
          </div>
        </div>
      )}
    </section>
  );
}

export default Testimonials;
