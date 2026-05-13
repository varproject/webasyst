import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import Image from "../components/Image";
import { url } from "../components/utils";

export default function() {
    const styles = [
        {key: 'primary', text: 'white', cardText: 'primary', title: 'Primary'},
        {key: 'secondary', text: 'dark', cardText: 'dark', title: 'Secondary'},
        {key: 'success', text: 'white', cardText: 'success', title: 'Success'},
        {key: 'danger', text: 'white', cardText: 'danger', title: 'Danger'},
        {key: 'warning', text: 'white', cardText: 'warning', title: 'Warning'},
        {key: 'info', text: 'white', cardText: 'info', title: 'Info'},
        {key: 'light', text: 'dark', cardText: 'dark', title: 'Light'},
        {key: 'dark', text: 'white', cardText: 'dark', title: 'Dark'},
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Cards"
                    subtitle="Bootstrap's cards provide a flexible and extensible content container with multiple variants and options."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Cards'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Below is an example of a basic card with mixed content and a fixed width. Cards have no fixed
                        width to start, so they'll naturally fill the full width of its parent element.
                    </p>

                    <Example>
                        <div className="card w-20x">
                            <Image
                                src="images/card-image-1.jpg"
                                size={[320, 180]}
                                className="card-img-top h-auto"
                                alt="Paradise Island"
                            />
                            <div className="card-body">
                                <h5 className="card-title">Card title</h5>
                                <p className="card-text">
                                    Some quick example text to build on the card title and make
                                    up the bulk of the card's content.
                                </p>
                                <a href="#" className="btn btn-primary">Go somewhere</a>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Content Types
                    </Anchor>

                    <p>
                        Cards support a wide variety of content, including images, text, list groups, links, and more.
                        Below are examples of what's supported.
                    </p>

                    <Anchor tag="h3">
                        Body
                    </Anchor>

                    <p>
                        The building block of a card is the <code>.card-body</code>. Use it whenever you need a padded
                        section within a card.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                This is some text within a card body.
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Titles, Text, And Links
                    </Anchor>

                    <p>
                        Card titles are used by adding <code>.card-title</code> to a <code>&lt;h*&gt;</code> tag. In the
                        same way, links are added and placed next to each other by adding <code>.card-link</code> to
                        an <code>&lt;a&gt;</code> tag.
                    </p>

                    <p>
                        Subtitles are used by adding a <code>.card-subtitle</code> to a <code>&lt;h*&gt;</code> tag. If
                        the <code>.card-title</code> and the <code>.card-subtitle</code> items are placed in
                        a <code>.card-body</code> item, the card title and subtitle are aligned nicely.
                    </p>

                    <Example>
                        <div className="card w-20x">
                            <div className="card-body">
                                <h5 className="card-title">Card title</h5>
                                <h6 className="card-subtitle mb-4 text-muted">Card subtitle</h6>
                                <p className="card-text">
                                    Some quick example text to build on the card title and make up the bulk of the
                                    card's content.
                                </p>
                                <a href="#" className="card-link">Card link</a>{' '}
                                <a href="#" className="card-link">Another link</a>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Images
                    </Anchor>

                    <p>
                        Use <code>.card-img-top</code> to places an image to the top of the card
                        and <code>.card-img-bottom</code> to place it to the bottom.
                    </p>

                    <Example>
                        <div className="row g-5">
                            <div className="col-12 col-sm-6">
                                <div className="card">
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="card-img-top h-auto"
                                        alt="Paradise Island"
                                    />
                                    <div className="card-body">
                                        <p className="card-text">
                                            Some quick example text to build on the card title and make up the bulk of the
                                            card's content.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="col-12 col-sm-6">
                                <div className="card">
                                    <div className="card-body">
                                        <p className="card-text">
                                            Some quick example text to build on the card title and make up the bulk of the
                                            card's content.
                                        </p>
                                    </div>
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="card-img-bottom h-auto"
                                        alt="Paradise Island"
                                    />
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        List groups
                    </Anchor>

                    <p>
                        Create lists of content in a card with a flush list group.
                    </p>

                    <Example>
                        <div className="card w-20x">
                            <ul className="list-group list-group-flush">
                                <li className="list-group-item">An item</li>
                                <li className="list-group-item">A second item</li>
                                <li className="list-group-item">A third item</li>
                            </ul>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Header And Footer
                    </Anchor>

                    <p>
                        Add an optional header and/or footer within a card.
                    </p>

                    <Example>
                        <div className="card">
                            <h5 className="card-header">Featured</h5>
                            <div className="card-body">
                                <h6 className="card-title">Special title treatment</h6>
                                <p className="card-text">
                                    With supporting text below as a natural lead-in to additional content.
                                </p>
                                <a href="#" className="btn btn-primary">Go somewhere</a>
                            </div>
                            <div className="card-footer text-muted">
                                <small>2 days ago</small>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Kitchen Sink
                    </Anchor>

                    <p>
                        Mix and match multiple content types to create the card you need, or throw everything in there.
                        Shown below are image styles, blocks, text styles, and a list group — all wrapped in a
                        fixed-width card.
                    </p>

                    <Example>
                        <div className="card w-20x">
                            <Image
                                src="images/card-image-1.jpg"
                                size={[320, 180]}
                                className="card-img-top h-auto"
                                alt="Paradise Island"
                            />
                            <div className="card-body">
                                <h5 className="card-title">Card title</h5>
                                <p className="card-text">Some quick example text to build on the card title and make
                                    up the bulk of the card's content.</p>
                            </div>
                            <ul className="list-group list-group-flush">
                                <li className="list-group-item">An item</li>
                                <li className="list-group-item">A second item</li>
                                <li className="list-group-item">A third item</li>
                            </ul>
                            <div className="card-footer">
                                <a href="#" className="card-link">Card link</a>
                                <a href="#" className="card-link">Another link</a>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Navigation
                    </Anchor>

                    <p>
                        Add some navigation to a card's header (or block) with Bootstrap's <a
                        href="/components-navs.html">nav</a> component or <a href="/components-tabs.html">tabs</a>.
                    </p>

                    <Example>
                        <div className="card text-center">
                            <div className="card-header">
                                <ul className="nav nav-tabs card-header-tabs">
                                    <li className="nav-item">
                                        <a className="nav-link active" aria-current="true" href="#">
                                            Active
                                            <span className="nav-link-sa-indicator" />
                                        </a>
                                    </li>
                                    <li className="nav-item">
                                        <a className="nav-link" href="#">Link</a>
                                    </li>
                                    <li className="nav-item">
                                        <a className="nav-link" href="#">Link</a>
                                    </li>
                                    <li className="nav-item">
                                        <a
                                            className="nav-link disabled"
                                            tabIndex={-1}
                                            aria-disabled="true"
                                        >
                                            Disabled
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div className="card-body">
                                <h5 className="card-title">Special title treatment</h5>
                                <p className="card-text">
                                    With supporting text below as a natural lead-in to additional content.
                                </p>
                                <a href="#" className="btn btn-primary">Go somewhere</a>
                            </div>
                        </div>
                    </Example>

                    <Example>
                        <div className="card text-center">
                            <div className="card-header">
                                <ul className="nav nav-pills card-header-pills">
                                    <li className="nav-item">
                                        <a className="nav-link active" href="#">Active</a>
                                    </li>
                                    <li className="nav-item">
                                        <a className="nav-link" href="#">Link</a>
                                    </li>
                                    <li className="nav-item">
                                        <a className="nav-link" href="#">Link</a>
                                    </li>
                                    <li className="nav-item">
                                        <a
                                            className="nav-link disabled"
                                            tabIndex={-1}
                                            aria-disabled="true"
                                        >
                                            Disabled
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div className="card-body">
                                <h5 className="card-title">Special title treatment</h5>
                                <p className="card-text">
                                    With supporting text below as a natural lead-in to additional content.
                                </p>
                                <a href="#" className="btn btn-primary">Go somewhere</a>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Image Overlays
                    </Anchor>

                    <p>
                        Turn an image into a card background and overlay your card's text. Depending on the image, you
                        may or may not need additional styles or utilities.
                    </p>

                    <Example>
                        <div className="card bg-dark text-white shadow-none">
                            <Image
                                src="images/card-image-1.jpg"
                                size={[640, 360]}
                                className="card-img h-auto"
                                alt="Paradise Island"
                            />
                            <div className="card-img-overlay">
                                <h5 className="card-title">Card title</h5>
                                <p className="card-text">
                                    This is a wider card with supporting text below as a natural lead-in to additional
                                    content. This content is a little bit longer.
                                </p>
                                <p className="card-text">Last updated 3 mins ago</p>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Horizontal
                    </Anchor>

                    <p>
                        Using a combination of grid and utility classes, cards can be made horizontal in a
                        mobile-friendly and responsive way. In the example below, we remove the grid gutters
                        with <code>.g-0</code> and use <code>.col-md-*</code> classes to make the card horizontal at
                        the <code>md</code> breakpoint. Further adjustments may be needed depending on your card
                        content.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="row g-0">
                                <div className="col-md-4">
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="h-100 w-100 object-fit-cover"
                                        alt="Paradise Island"
                                    />
                                </div>
                                <div className="col-md-8">
                                    <div className="card-body">
                                        <h5 className="card-title">Card title</h5>
                                        <p className="card-text">
                                            This is a wider card with supporting text below as a natural lead-in to
                                            additional content. This content is a little bit longer.
                                        </p>
                                        <p className="card-text">
                                            <small className="text-muted">Last updated 3 mins ago</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Card Styles
                    </Anchor>

                    <p>
                        Cards include various options for customizing their backgrounds, borders, and color.
                    </p>

                    <Anchor tag="h3">
                        Background And Color
                    </Anchor>

                    <p>
                        Use <a href="https://getbootstrap.com/docs/5.0/utilities/colors/">text color</a> and <a
                        href="https://getbootstrap.com/docs/5.0/utilities/background/">background utilities</a> to
                        change the appearance of a card.
                    </p>

                    <Example>
                        <div className="row g-5">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-6">
                                    <div className={`card text-${style.text} bg-${style.key} shadow-none`}>
                                        <div className="card-header">Header</div>
                                        <div className={`card-body text-${style.text}`}>
                                            <h5 className="card-title">{style.title} card title</h5>
                                            <p className="card-text">
                                                Some quick example text to build on the card title and make up the bulk
                                                of the card's content.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Border
                    </Anchor>

                    <p>
                        Use <a href="https://getbootstrap.com/docs/5.0/utilities/borders/">border utilities</a> to
                        change just the <code>border-color</code> of a card. Note that you can
                        put <code>.text-{'{'}color{'}'}</code> classes on the parent <code>.card</code> or a subset of
                        the card's contents as shown below.
                    </p>

                    <Example>
                        <div className="row g-5">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-6">
                                    <div className={`card card-sa-border border-${style.key}`}>
                                        <div className={`card-header border-${style.key}`}>Header</div>
                                        <div className={`card-body text-${style.cardText}`}>
                                            <h5 className="card-title">{style.title} card title</h5>
                                            <p className="card-text">
                                                Some quick example text to build on the card title and make up the bulk
                                                of the card's content.
                                            </p>
                                        </div>
                                        <div className={`card-footer border-${style.key}`}>Footer</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Card Layout
                    </Anchor>

                    <p>
                        In addition to styling the content within cards, Bootstrap includes a few options for laying out
                        series of cards. For the time being, <strong>these layout options are not yet
                        responsive</strong>.
                    </p>

                    <Anchor tag="h3">
                        Card Groups
                    </Anchor>

                    <p>
                        Use card groups to render cards as a single, attached element with equal width and height
                        columns. Card groups start off stacked and use <code>display: flex;</code> to become attached
                        with uniform dimensions starting at the <code>sm</code> breakpoint.
                    </p>

                    <Example>
                        <div className="card-group">
                            <div className="card">
                                <Image
                                    src="images/card-image-1.jpg"
                                    size={[640, 360]}
                                    className="card-img-top h-auto"
                                    alt="Paradise Island"
                                />
                                <div className="card-body">
                                    <h5 className="card-title">Card title</h5>
                                    <p className="card-text">
                                        This is a wider card with supporting text below as a natural lead-in to
                                        additional content. This content is a little bit longer.
                                    </p>
                                </div>
                                <div className="card-footer">
                                    <small className="text-muted">Last updated 3 mins ago</small>
                                </div>
                            </div>
                            <div className="card">
                                <Image
                                    src="images/card-image-1.jpg"
                                    size={[640, 360]}
                                    className="card-img-top h-auto"
                                    alt="Paradise Island"
                                />
                                <div className="card-body">
                                    <h5 className="card-title">Card title</h5>
                                    <p className="card-text">
                                        This card has supporting text below as a natural lead-in to additional content.
                                    </p>
                                </div>
                                <div className="card-footer">
                                    <small className="text-muted">Last updated 3 mins ago</small>
                                </div>
                            </div>
                            <div className="card">
                                <Image
                                    src="images/card-image-1.jpg"
                                    size={[640, 360]}
                                    className="card-img-top h-auto"
                                    alt="Paradise Island"
                                />
                                <div className="card-body">
                                    <h5 className="card-title">Card title</h5>
                                    <p className="card-text">
                                        This is a wider card with supporting text below as a natural lead-in to
                                        additional content. This card has even longer content than the first to show
                                        that equal height action.
                                    </p>
                                </div>
                                <div className="card-footer">
                                    <small className="text-muted">Last updated 3 mins ago</small>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Grid Cards
                    </Anchor>

                    <p>
                        Use the Bootstrap grid system and its <a
                        href="https://getbootstrap.com/docs/5.0/layout/grid/#row-columns"><code>.row-cols</code> classes</a> to
                        control how many grid columns (wrapped around your cards) you show per row. For example,
                        here's <code>.row-cols-1</code> laying out the cards on one column,
                        and <code>.row-cols-md-2</code> splitting four cards to equal width across multiple rows, from
                        the medium breakpoint up.
                    </p>

                    <Example>
                        <div className="row row-cols-1 row-cols-md-2 g-5">
                            <div className="col">
                                <div className="card h-100">
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="card-img-top h-auto"
                                        alt="Paradise Island"
                                    />
                                    <div className="card-body">
                                        <h5 className="card-title">Card title</h5>
                                        <p className="card-text">
                                            This is a longer card with supporting text below as a natural lead-in to
                                            additional content. This content is a little bit longer.
                                        </p>
                                    </div>
                                    <div className="card-footer">
                                        <small className="text-muted">Last updated 3 mins ago</small>
                                    </div>
                                </div>
                            </div>
                            <div className="col">
                                <div className="card h-100">
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="card-img-top h-auto"
                                        alt="Paradise Island"
                                    />
                                    <div className="card-body">
                                        <h5 className="card-title">Card title</h5>
                                        <p className="card-text">
                                            This is a short card.
                                        </p>
                                    </div>
                                    <div className="card-footer">
                                        <small className="text-muted">Last updated 3 mins ago</small>
                                    </div>
                                </div>
                            </div>
                            <div className="col">
                                <div className="card h-100">
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="card-img-top h-auto"
                                        alt="Paradise Island"
                                    />
                                    <div className="card-body">
                                        <h5 className="card-title">Card title</h5>
                                        <p className="card-text">
                                            This is a longer card with supporting text below as a natural lead-in to
                                            additional content. This content is a little bit longer.
                                        </p>
                                    </div>
                                    <div className="card-footer">
                                        <small className="text-muted">Last updated 3 mins ago</small>
                                    </div>
                                </div>
                            </div>
                            <div className="col">
                                <div className="card h-100">
                                    <Image
                                        src="images/card-image-1.jpg"
                                        size={[640, 360]}
                                        className="card-img-top h-auto"
                                        alt="Paradise Island"
                                    />
                                    <div className="card-body">
                                        <h5 className="card-title">Card title</h5>
                                        <p className="card-text">
                                            This is a longer card with supporting text below as a natural lead-in to
                                            additional content. This content is a little bit longer.
                                        </p>
                                    </div>
                                    <div className="card-footer">
                                        <small className="text-muted">Last updated 3 mins ago</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
