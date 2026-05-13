import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const longModalContent = (
        <React.Fragment>
            <p>
                What follows is just some placeholder text for this modal dialog. You just gotta ignite the light and
                let it shine! Come just as you are to me. Just own the night like the 4th of July. Infect me with your
                love and fill me with your poison. Come just as you are to me. End of the rainbow looking treasure.
            </p>
            <p>
                I can't sleep let's run away and don't ever look back, don't ever look back. I can't sleep let's run
                away and don't ever look back, don't ever look back. Yes, we make angels cry, raining down on earth from
                up above. I'm walking on air (tonight). Let you put your hands on me in my skin-tight jeans. Stinging
                like a bee I earned my stripes. I went from zero, to my own hero. Even brighter than the moon, moon,
                moon. Make 'em go, 'Aah, aah, aah' as you shoot across the sky-y-y! Why don't you let me stop by?
            </p>
            <p>
                Boom, boom, boom. Never made me blink one time. Yeah, you're lucky if you're on her plane. Talk about
                our future like we had a clue. Oh my God no exaggeration. You're original, cannot be replaced. The
                girl's a freak, she drive a jeep in Laguna Beach. It's no big deal, it's no big deal, it's no big deal.
                In another life I would make you stay. I'm ma get your heart racing in my skin-tight jeans. I wanna walk
                on your wave length and be there when you vibrate Never made me blink one time.
            </p>
            <p>
                We'd keep all our promises be us against the world. In another life I would be your girl. We can dance,
                until we die, you and I, will be young forever. And on my 18th Birthday we got matching tattoos. So open
                up your heart and just let it begin. 'Cause she's the muse and the artist. She eats your heart out. Like
                Jeffrey Dahmer (woo). Pop your confetti. (This is how we do) I know one spark will shock the world, yeah
                yeah. If you only knew what the future holds.
            </p>
            <p>
                Sipping on Rosé, Silver Lake sun, coming up all lazy. It's in the palm of your hand now baby. So we hit
                the boulevard. So make a wish, I'll make it like your birthday everyday. Do you ever feel already buried
                deep six feet under? It's time to bring out the big balloons. You could've been the greatest. Passport
                stamps, she's cosmopolitan. Your kiss is cosmic, every move is magic.
            </p>
            <p>
                We're living the life. We're doing it right. Open up your heart. I was tryna hit it and quit it. Her
                love is like a drug. Always leaves a trail of stardust. The girl's a freak, she drive a jeep in Laguna
                Beach. Fine, fresh, fierce, we got it on lock. All my girls vintage Chanel baby.
            </p>
            <p>
                Before you met me I was alright but things were kinda heavy. Peach-pink lips, yeah, everybody stares.
                This is no big deal. Calling out my name. I could have rewrite your addiction. She's got that, je ne
                sais quoi, you know it. Heavy is the head that wears the crown. 'Cause, baby, you're a firework. Like
                thunder gonna shake the ground.
            </p>
            <p>
                Just own the night like the 4th of July! I'm gon' put her in a coma. What you're waiting for, it's time
                for you to show it off. Can't replace you with a million rings. You open my eyes and I'm ready to go,
                lead me into the light. And here you are. I'm gon' put her in a coma. Come on, let your colours burst.
                So cover your eyes, I have a surprise. As I march alone to a different beat. Glitter all over the room
                pink flamingos in the pool.
            </p>
        </React.Fragment>
    );

    const mediumModalContent = (
        <React.Fragment>
            <p>
                Placeholder text for this demonstration of a vertically centered modal dialog.
            </p>
            <p>
                In this case, the dialog has a bit more content, just to show how vertical centering can be added to a
                scrollable modal.
            </p>
            <p>
                What follows is just some placeholder text for this modal dialog. Sipping on Rosé, Silver Lake sun,
                coming up all lazy. It's in the palm of your hand now baby. So we hit the boulevard. So make a wish,
                I'll make it like your birthday everyday. Do you ever feel already buried deep six feet under? It's time
                to bring out the big balloons. You could've been the greatest. Passport stamps, she's cosmopolitan. Your
                kiss is cosmic, every move is magic.
            </p>
            <p>
                We're living the life. We're doing it right. Open up your heart. I was tryna hit it and quit it. Her
                love is like a drug. Always leaves a trail of stardust. The girl's a freak, she drive a jeep in Laguna
                Beach. Fine, fresh, fierce, we got it on lock. All my girls vintage Chanel baby.
            </p>
        </React.Fragment>
    );

    return (
        <Layout>
            <App>
                <Article
                    title="Modal"
                    subtitle="Use Bootstrap's JavaScript modal plugin to add dialogs to your site for lightboxes, user notifications, or completely custom content."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Modal'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Below is a <em>static</em> modal example (meaning
                        its <code>position</code> and <code>display</code> have been overridden). Included are the modal
                        header, modal body (required for <code>padding</code>), and modal footer (optional). We ask that
                        you include modal headers with dismiss actions whenever possible, or provide another explicit
                        dismiss action.
                    </p>

                    <Example>
                        <div className="modal" tabIndex={-1}>
                            <div className="modal-dialog">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title">
                                            Modal title
                                        </h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        <p>Modal body text goes here.</p>
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Live Demo
                    </Anchor>

                    <p>
                        Toggle a working modal demo by clicking the button below. It will slide down and fade in from
                        the top of the page.
                    </p>

                    <Example>
                        <button
                            type="button"
                            className="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModal"
                        >
                            Launch demo modal
                        </button>

                        <div className="modal fade" id="exampleModal" tabIndex={-1} aria-labelledby="exampleModalLabel"
                             aria-hidden="true">
                            <div className="modal-dialog">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalLabel">Modal title</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        Woohoo, you're reading this text in a modal!
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Static Backdrop
                    </Anchor>

                    <p>
                        When backdrop is set to static, the modal will not close when clicking outside it. Click the
                        button below to try it.
                    </p>

                    <Example>
                        <button
                            type="button"
                            className="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#staticBackdrop"
                        >
                            Launch static backdrop modal
                        </button>

                        <div
                            className="modal fade"
                            id="staticBackdrop"
                            data-bs-backdrop="static"
                            data-bs-keyboard="false"
                            tabIndex={-1}
                            aria-labelledby="staticBackdropLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="staticBackdropLabel">Modal title</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        I will not close if you click outside me. Don't even try to press escape key.
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Understood</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Scrolling Long Content
                    </Anchor>

                    <p>
                        When modals become too long for the user's viewport or device, they scroll independent of the
                        page itself. Try the demo below to see what we mean.
                    </p>

                    <Example>
                        <button
                            type="button"
                            className="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModalLong"
                        >
                            Launch demo modal
                        </button>

                        <div
                            className="modal fade"
                            id="exampleModalLong"
                            tabIndex={-1}
                            aria-labelledby="exampleModalLongTitle"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalLongTitle">Modal title</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        {longModalContent}
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <p>
                        You can also create a scrollable modal that allows scroll the modal body by
                        adding <code>.modal-dialog-scrollable</code> to <code>.modal-dialog</code>.
                    </p>

                    <Example>
                        <button
                            type="button"
                            className="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModalScrollable"
                        >
                            Launch demo modal
                        </button>

                        <div
                            className="modal fade"
                            id="exampleModalScrollable"
                            tabIndex={-1}
                            aria-labelledby="exampleModalScrollableTitle"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog modal-dialog-scrollable">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalScrollableTitle">Modal title</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        {longModalContent}
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Vertically Centered
                    </Anchor>

                    <p>
                        Add <code>.modal-dialog-centered</code> to <code>.modal-dialog</code> to vertically center the
                        modal.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exampleModalCenter"
                                >
                                    Vertically centered modal
                                </button>
                            </div>
                            <div className="col-auto">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exampleModalCenteredScrollable"
                                >
                                    Vertically centered scrollable modal
                                </button>
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModalCenter"
                            tabIndex={-1}
                            aria-labelledby="exampleModalCenterTitle"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog modal-dialog-centered">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalCenterTitle">Modal title</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        <p>
                                            Placeholder text for this demonstration of a vertically centered modal
                                            dialog.
                                        </p>
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModalCenteredScrollable"
                            tabIndex={-1}
                            aria-labelledby="exampleModalCenteredScrollableTitle"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalCenteredScrollableTitle">
                                            Modal title
                                        </h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        {mediumModalContent}
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button type="button" className="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Optional Sizes
                    </Anchor>

                    <p>
                        Modals have three optional sizes, available via modifier classes to be placed on
                        a <code>.modal-dialog</code>. These sizes kick in at certain breakpoints to avoid horizontal
                        scrollbars on narrower viewports.
                    </p>

                    <table className="table">
                        <thead>
                        <tr>
                            <th>Size</th>
                            <th>Class</th>
                            <th>Modal max-width</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Small</td>
                            <td><code>.modal-sm</code></td>
                            <td><code>300px</code></td>
                        </tr>
                        <tr>
                            <td>Default</td>
                            <td className="text-muted">None</td>
                            <td><code>500px</code></td>
                        </tr>
                        <tr>
                            <td>Large</td>
                            <td><code>.modal-lg</code></td>
                            <td><code>800px</code></td>
                        </tr>
                        <tr>
                            <td>Extra large</td>
                            <td><code>.modal-xl</code></td>
                            <td><code>1140px</code></td>
                        </tr>
                        </tbody>
                    </table>

                    <p>
                        Our default modal without modifier class constitutes the "medium" size modal.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exampleModalXl"
                                >
                                    Extra large modal
                                </button>
                            </div>
                            <div className="col-auto">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exampleModalLg"
                                >
                                    Large modal
                                </button>
                            </div>
                            <div className="col-auto">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exampleModalNl"
                                >
                                    Normal modal
                                </button>
                            </div>
                            <div className="col-auto">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exampleModalSm"
                                >
                                    Small modal
                                </button>
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModalXl"
                            tabIndex={-1}
                            aria-labelledby="exampleModalXlLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog modal-xl">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalXlLabel">Extra large modal</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        ...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModalLg"
                            tabIndex={-1}
                            aria-labelledby="exampleModalLgLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog modal-lg">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalLgLabel">Large modal</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        ...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModalNl"
                            tabIndex={-1}
                            aria-labelledby="exampleModalNlLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalNlLabel">Normal modal</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        ...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModalSm"
                            tabIndex={-1}
                            aria-labelledby="exampleModalSmLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog modal-sm">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title" id="exampleModalSmLabel">Small modal</h5>
                                        <button
                                            type="button"
                                            className="sa-close sa-close--modal"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="modal-body">
                                        ...
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
