const appendLine = (content, value, { strong = false } = {}) => {
  if (content.childNodes.length > 0) {
    content.append(document.createElement("br"));
  }

  if (strong) {
    const element = document.createElement("strong");
    element.textContent = value ?? "";
    content.append(element);
    return;
  }

  content.append(document.createTextNode(value ?? ""));
};

const rep = (point) => {
  const content = document.createElement("div");
  appendLine(content, point.name, { strong: true });
  appendLine(
    content,
    `${point.seen_at ?? ""} (${Number(point.minutes_ago) || 0}m)`
  );

  if (point.accuracy) {
    appendLine(content, `±${Math.round(Number(point.accuracy))}m`);
  }

  return content;
};

const customer = (point) => {
  const content = document.createElement("div");
  appendLine(content, point.name, { strong: true });

  if (point.code) {
    appendLine(content, `#${point.code}`);
  }

  if (point.route) {
    appendLine(content, point.route);
  }

  return content;
};

globalThis.JawlaMapPopups = Object.freeze({ rep, customer });
