(function (blocks, element, serverSideRender, editor) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;

  var ServerSideRenderComponent =
    serverSideRender ||
    (editor ? editor.ServerSideRender : null) ||
    (window.wp ? window.wp.serverSideRender : null);

  // Block 1: Contact Form
  registerBlockType("clean-contact-form/form", {
    title: "Clean Contact Form",
    icon: "email",
    category: "widgets",

    edit: function (props) {
      if (!ServerSideRenderComponent) {
        return el("p", {}, "ServerSideRender component unavailable.");
      }

      return el(
        "div",
        { className: props.className },
        el(ServerSideRenderComponent, {
          block: "clean-contact-form/form",
          attributes: props.attributes,
        }),
      );
    },
    save: function () {
      // Dynamic block; rendered via PHP
      return null;
    },
  });

  // Block 2: Mailing List Signup
  registerBlockType("clean-contact-form/mailing-list", {
    title: "Mailing List Signup",
    icon: "email-alt",
    category: "widgets",

    edit: function (props) {
      if (!ServerSideRenderComponent) {
        return el("p", {}, "ServerSideRender component unavailable.");
      }

      return el(
        "div",
        { className: props.className },
        el(ServerSideRenderComponent, {
          block: "clean-contact-form/mailing-list",
          attributes: props.attributes,
        }),
      );
    },
    save: function () {
      // Dynamic block; rendered via PHP
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.serverSideRender ||
    (window.wp.editor ? window.wp.editor.ServerSideRender : null),
  window.wp.editor,
);
