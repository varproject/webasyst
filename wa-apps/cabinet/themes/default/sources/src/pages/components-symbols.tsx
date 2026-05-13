import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import classnames from "classnames";
import Image from "../components/Image";
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();
    const imageSize = 128;

    return (
        <Layout>
            <App>
                <Article
                    title="Symbols"
                    subtitle="Documentation and examples for symbols, our component for displaying user avatars and initials."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Symbols'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Typically, a symbol consists of a <code>.sa-symbol</code> container and an{' '}
                        <code>{'<img>'}</code> child element.
                    </p>

                    <Example>
                        <div className="sa-symbol sa-symbol--shape--circle sa-symbol--size--xxl">
                            <Image src="images/customers/customer-4.jpg" size={imageSize} />
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Sizes
                    </Anchor>

                    <p>
                        Use the <code>.sa-symbol--size--*</code> classes to set the size of the symbol.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].map((size, sizeIdx) => (
                                <div key={sizeIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle sa-symbol--size--${size}`}>
                                        <Image src="images/customers/customer-4.jpg" size={imageSize} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <p>
                        You can also set the symbol size using any font size utilities such as <code>fs-*</code>.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['fs-6', 'fs-5', 'fs-4', 'fs-3', 'fs-2', 'fs-1'].map((size, sizeIdx) => (
                                <div key={sizeIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle ${size}`}>
                                        <Image src="images/customers/customer-4.jpg" size={imageSize} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Initials
                    </Anchor>

                    <p>
                        Display initials using the <code>.sa-symbol__text</code> element instead of{' '}
                        <code>{'<img>'}</code> if the user does not have an avatar.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].map((size, sizeIdx) => (
                                <div key={sizeIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle sa-symbol--size--${size}`}>
                                        <div className="sa-symbol__text">GB</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Status Badge
                    </Anchor>

                    <p>
                        Need to display user status? No problem, use the <code>.sa-symbol__status</code> element and
                        the <code>.sa-symbol--status--*</code> modifier.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['offline', 'online', 'away', 'busy'].map((status, statusIdx) => (
                                <div key={statusIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle sa-symbol--size--lg sa-symbol--status--${status}`}>
                                        <Image src="images/customers/customer-4.jpg" size={imageSize} />
                                        <div className="sa-symbol__status" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <p>
                        Status in symbols of different sizes.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].map((size, sizeIdx) => (
                                <div key={sizeIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle sa-symbol--size--${size} sa-symbol--status--online`}>
                                        <Image src="images/customers/customer-4.jpg" size={imageSize} />
                                        <div className="sa-symbol__status" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Shape
                    </Anchor>

                    <p>
                        The shape of the symbol is set using the <code>.sa-symbol--shape--*</code> modifier.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['', 'rounded', 'circle'].map((shape, shapeIdx) => (
                                <div key={shapeIdx} className="col-auto">
                                    <div className={classnames('sa-symbol sa-symbol--size--lg', {[`sa-symbol--shape--${shape}`]: shape})}>
                                        <Image src="images/customers/customer-4.jpg" size={imageSize} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Icons
                    </Anchor>

                    <p>
                        Use the <code>.sa-symbol__icon</code> element to place the icon inside the symbol.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].map((size, sizeIdx) => (
                                <div key={sizeIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle sa-symbol--size--${size}`}>
                                        <div className="sa-symbol__icon">
                                            {svg('fontawesome/fas fa-bacterium')}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Styles
                    </Anchor>

                    <p>
                        The style of the symbol is set using the <code>.sa-symbol--style--*</code> modifier.
                    </p>

                    <Example>
                        <div className="row g-3 align-items-center">
                            {['theme', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'].map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <div className={`sa-symbol sa-symbol--shape--circle sa-symbol--size--lg sa-symbol--style--${style}`}>
                                        <div className="sa-symbol__text">GB</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
