import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import Image from "../components/Image";
import { url } from "../components/utils";

function LocalImage({src}: {src: string}) {
    return (
        <Image src={src} size={[720, 405]} className="d-block w-100 h-auto" />
    );
}

export default function() {
    return (
        <Layout>
            <App>
                <Article
                    title="Carousel"
                    subtitle="A slideshow component for cycling through elements — images or slides of text — like a carousel."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Carousel'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Here's a carousel with slides only. Note the presence of
                        the <code>.d-block</code> and <code>.w-100</code> on carousel images to prevent browser default
                        image alignment.
                    </p>

                    <Example>
                        <div id="carouselExampleSlidesOnly" className="carousel slide" data-bs-ride="carousel">
                            <div className="carousel-inner">
                                <div className="carousel-item active">
                                    <LocalImage src="images/carousel-1.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-2.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-3.jpg" />
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        With Controls
                    </Anchor>

                    <p>
                        Adding in the previous and next controls. We recommend
                        using <code>&lt;button&gt;</code> elements, but you can also use <code>&lt;a&gt;</code> elements
                        with <code>role="button"</code>.
                    </p>

                    <Example>
                        <div id="carouselExampleControls" className="carousel slide" data-bs-ride="carousel">
                            <div className="carousel-inner">
                                <div className="carousel-item active">
                                    <LocalImage src="images/carousel-1.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-2.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-3.jpg" />
                                </div>
                            </div>
                            <button
                                className="carousel-control-prev"
                                type="button"
                                data-bs-target="#carouselExampleControls"
                                data-bs-slide="prev"
                            >
                                <span className="carousel-control-prev-icon" aria-hidden="true" />
                                <span className="visually-hidden">Previous</span>
                            </button>
                            <button
                                className="carousel-control-next"
                                type="button"
                                data-bs-target="#carouselExampleControls"
                                data-bs-slide="next"
                            >
                                <span className="carousel-control-next-icon" aria-hidden="true" />
                                <span className="visually-hidden">Next</span>
                            </button>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        With Indicators
                    </Anchor>

                    <p>
                        You can also add the indicators to the carousel, alongside the controls, too.
                    </p>

                    <Example>
                        <div id="carouselExampleIndicators" className="carousel slide" data-bs-ride="carousel">
                            <div className="carousel-indicators">
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleIndicators"
                                    data-bs-slide-to="0"
                                    className="active"
                                    aria-current="true"
                                    aria-label="Slide 1"
                                />
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleIndicators"
                                    data-bs-slide-to="1"
                                    aria-label="Slide 2"
                                />
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleIndicators"
                                    data-bs-slide-to="2"
                                    aria-label="Slide 3"
                                />
                            </div>
                            <div className="carousel-inner">
                                <div className="carousel-item active">
                                    <LocalImage src="images/carousel-1.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-2.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-3.jpg" />
                                </div>
                            </div>
                            <button
                                className="carousel-control-prev"
                                type="button"
                                data-bs-target="#carouselExampleIndicators"
                                data-bs-slide="prev"
                            >
                                <span className="carousel-control-prev-icon" aria-hidden="true" />
                                <span className="visually-hidden">Previous</span>
                            </button>
                            <button
                                className="carousel-control-next"
                                type="button"
                                data-bs-target="#carouselExampleIndicators"
                                data-bs-slide="next"
                            >
                                <span className="carousel-control-next-icon" aria-hidden="true" />
                                <span className="visually-hidden">Next</span>
                            </button>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        With Captions
                    </Anchor>

                    <p>
                        Add captions to your slides easily with the <code>.carousel-caption</code> element within
                        any <code>.carousel-item</code>. They can be easily hidden on smaller viewports, as shown below,
                        with optional <a href="https://getbootstrap.com/docs/5.0/utilities/display/">display
                        utilities</a>. We hide them initially with <code>.d-none</code> and bring them back on
                        medium-sized devices with <code>.d-md-block</code>.
                    </p>

                    <Example>
                        <div id="carouselExampleCaptions" className="carousel slide" data-bs-ride="carousel">
                            <div className="carousel-indicators">
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleCaptions"
                                    data-bs-slide-to="0"
                                    className="active"
                                    aria-current="true"
                                    aria-label="Slide 1"
                                />
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleCaptions"
                                    data-bs-slide-to="1"
                                    aria-label="Slide 2"
                                />
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleCaptions"
                                    data-bs-slide-to="2"
                                    aria-label="Slide 3"
                                />
                            </div>
                            <div className="carousel-inner">
                                <div className="carousel-item active">
                                    <LocalImage src="images/carousel-with-captions-1.jpg" />
                                    <div className="carousel-caption d-none d-md-block">
                                        <h5>Mars</h5>
                                        <p>Some representative placeholder content for the first slide.</p>
                                    </div>
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-with-captions-2.jpg" />
                                    <div className="carousel-caption d-none d-md-block">
                                        <h5>Earth</h5>
                                        <p>Some representative placeholder content for the second slide.</p>
                                    </div>
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-with-captions-3.jpg" />
                                    <div className="carousel-caption d-none d-md-block">
                                        <h5>Neptune</h5>
                                        <p>Some representative placeholder content for the third slide.</p>
                                    </div>
                                </div>
                            </div>
                            <button
                                className="carousel-control-prev"
                                type="button"
                                data-bs-target="#carouselExampleCaptions"
                                data-bs-slide="prev"
                            >
                                <span className="carousel-control-prev-icon" aria-hidden="true" />
                                <span className="visually-hidden">Previous</span>
                            </button>
                            <button
                                className="carousel-control-next"
                                type="button"
                                data-bs-target="#carouselExampleCaptions"
                                data-bs-slide="next"
                            >
                                <span className="carousel-control-next-icon" aria-hidden="true" />
                                <span className="visually-hidden">Next</span>
                            </button>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Crossfade
                    </Anchor>

                    <p>
                        Add <code>.carousel-fade</code> to your carousel to animate slides with a fade transition
                        instead of a slide.
                    </p>

                    <Example>
                        <div id="carouselExampleFade" className="carousel slide carousel-fade" data-bs-ride="carousel">
                            <div className="carousel-inner">
                                <div className="carousel-item active">
                                    <LocalImage src="images/carousel-1.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-2.jpg" />
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-3.jpg" />
                                </div>
                            </div>
                            <button
                                className="carousel-control-prev"
                                type="button"
                                data-bs-target="#carouselExampleFade"
                                data-bs-slide="prev"
                            >
                                <span className="carousel-control-prev-icon" aria-hidden="true" />
                                <span className="visually-hidden">Previous</span>
                            </button>
                            <button
                                className="carousel-control-next"
                                type="button"
                                data-bs-target="#carouselExampleFade"
                                data-bs-slide="next"
                            >
                                <span className="carousel-control-next-icon" aria-hidden="true" />
                                <span className="visually-hidden">Next</span>
                            </button>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Dark Variant
                    </Anchor>

                    <p>
                        Add <code>.carousel-dark</code> to the <code>.carousel</code> for darker controls, indicators,
                        and captions. Controls have been inverted from their default white fill with
                        the <code>filter</code> CSS property. Captions and controls have additional Sass variables that
                        customize the <code>color</code> and <code>background-color</code>.
                    </p>

                    <Example>
                        <div id="carouselExampleDark" className="carousel carousel-dark slide" data-bs-ride="carousel">
                            <div className="carousel-indicators">
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleDark"
                                    data-bs-slide-to="0"
                                    className="active"
                                    aria-current="true"
                                    aria-label="Slide 1"
                                />
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleDark"
                                    data-bs-slide-to="1"
                                    aria-label="Slide 2"
                                />
                                <button
                                    type="button"
                                    data-bs-target="#carouselExampleDark"
                                    data-bs-slide-to="2"
                                    aria-label="Slide 3"
                                />
                            </div>
                            <div className="carousel-inner">
                                <div className="carousel-item active" data-bs-interval="10000">
                                    <LocalImage src="images/carousel-dark-1.jpg" />
                                    <div className="carousel-caption d-none d-md-block">
                                        <h5>First slide label</h5>
                                        <p>Some representative placeholder content for the first slide.</p>
                                    </div>
                                </div>
                                <div className="carousel-item" data-bs-interval="2000">
                                    <LocalImage src="images/carousel-dark-2.jpg" />
                                    <div className="carousel-caption d-none d-md-block">
                                        <h5>Second slide label</h5>
                                        <p>Some representative placeholder content for the second slide.</p>
                                    </div>
                                </div>
                                <div className="carousel-item">
                                    <LocalImage src="images/carousel-dark-3.jpg" />
                                    <div className="carousel-caption d-none d-md-block">
                                        <h5>Third slide label</h5>
                                        <p>Some representative placeholder content for the third slide.</p>
                                    </div>
                                </div>
                            </div>
                            <button
                                className="carousel-control-prev"
                                type="button"
                                data-bs-target="#carouselExampleDark"
                                data-bs-slide="prev"
                            >
                                <span className="carousel-control-prev-icon" aria-hidden="true" />
                                <span className="visually-hidden">Previous</span>
                            </button>
                            <button
                                className="carousel-control-next"
                                type="button"
                                data-bs-target="#carouselExampleDark"
                                data-bs-slide="next"
                            >
                                <span className="carousel-control-next-icon" aria-hidden="true" />
                                <span className="visually-hidden">Next</span>
                            </button>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
