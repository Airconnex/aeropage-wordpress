import React, { useState, useEffect } from "react";
import { useSearchParams } from "react-router-dom";
import {
  BrowserRouter as Router,
  Switch,
  Route,
  Link,
  BrowserRouter,
  Routes,
} from "react-router-dom";
import {
  tickIcon,
  trashIcon,
  refreshIcon,
  settingsIcon,
  warningIcon,
  squareMessageIcon,
  airtableIcon,
  aeroIconBlack
} from "./Icons";
import axios from "axios";

const Card = ({
  el, 
  idx,
  setUrl,
  setEditID,
  setIdx,
  url,
  handleClick,
  handleRefresh,
  setOpenModal,
  setToBeDeleted,
  setOpenLogModal,
  setSyncLog
}) => {
  const link = `${MYSCRIPT.plugin_admin_path}admin.php?page=${MYSCRIPT.plugin_name}&path=editPost`;
  const [refreshState, setRefreshState] = useState(false);
  const [syncTime, setSyncTime] = useState(el.sync_time);
  const [syncStatus, setSyncStatus] = useState(el.sync_status);

  return (
    <div
      style={{
        border: "1px solid #B9B9B9",
        padding: "10px",
        minWidth: "150px",
        maxWidth: "250px",
        width: "250px",
        display: "flex",
        flexDirection: "column",
        boxShadow: "0px 4px 4px 0px #00000040",
        flex: "1 1 200px",
        margin: "10px 10px 10px 10px",
        borderRadius: "8px",
      }}
      key={idx}
    >
      <Link
        className="link"
        onClick={() => {
          setUrl(!url);
          setEditID(el.ID);
          setIdx(idx);
        }}
        style={{
          textDecoration: "none",
          color: "black",
        }}
        to={`${link}&id=${el.ID}`}
      >
        <div
          style={{
            borderBottom: "1px solid #F4F5F8",
            display: "flex",
            width: "100%",
            paddingBottom: "10px",
          }}
          title="Edit Post Type"
        >
          <div
            style={{
              display: "flex",
              flexDirection: "row",
              width: "100%",
            }}
          >
            <span
              style={{
                fontFamily: "'Inter', sans-serif",
                fontStyle: "normal",
                fontWeight: "600",
                whiteSpace: "nowrap",
                display: "flex",
                alignItems: "center",
                width: "fit-content",
                color: "#595B5C",
                fontSize: "12px",
                lineHeight: "16.8px",
              }}
            >
              {el?.post_title}
            </span>
            {/* <button onClick={() => handleClick(el?.ID)}>
        Refresh
      </button> */}

            <div
              style={{
                display: "flex",
                width: "100%",
                justifyContent: "right",
              }}
            >
              <div
                style={{
                  display: "flex",
                  justifyContent: "center",
                  alignItems: "center",
                  padding: "7px",
                  borderRadius: "3px",
                  background: syncStatus === "success" ? "#25A6A61A" : "rgba(194, 37, 37, 0.1)",
                }}
                title={`Sync ${syncStatus}`}
              >
                {syncStatus === "success" ? tickIcon : warningIcon}
              </div>
            </div>
          </div>
        </div>
      </Link>

      <div
        style={{
          height: "100%",
          display: "flex",
          // alignItems: "center",
          flexDirection: "column",
        }}
      >
        <span
          style={{
            color: "#595B5C",
            fontFamily: "'Inter', sans-serif",
            fontStyle: "normal",
            fontWeight: "400",
            fontSize: "10px",
            height: "100%",
            display: "flex",
            alignItems: "center",
            lineHeight: "17.5px",
            paddingTop: "10px",
            paddingBottom: "10px",
          }}
        >
          {syncTime && (
            <>
              Updated { new Date(parseInt(syncTime) * 1000).toLocaleString() }
            </>
          )}
        </span>
        <div
          style={{
            width: "100%",
            height: "100%",
            display: "flex",
            justifyContent: "center",
            alignItems: "end",
          }}
        >
          <a href={`https://airtable.com/${el.connection}`} target={"_blank"}>
            <div
              id="airtable-link"
              style={{
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                padding: "7px",
                height: "28px",
                width: "28px",
                cursor: "pointer"
              }}
              title="Open Airtable"
            >
              {airtableIcon}
            </div>
          </a>
          <div
            id="logs"
            style={{
              display: "flex",
              justifyContent: "center",
              alignItems: "center",
              padding: "7px",
              height: "28px",
              width: "28px",
              cursor: "pointer"
            }}
            title="Recent Sync Log"
            onClick={(e) => {
              setOpenLogModal(true);
              setSyncLog(el.sync_message);
            }}
          >
            {squareMessageIcon}
          </div>
          <div
            id="trash"
            style={{
              display: "flex",
              justifyContent: "center",
              alignItems: "center",
              padding: "7px",
              height: "28px",
              width: "28px",
              cursor: "pointer"
            }}
            onClick={(e) => {
              setOpenModal(true);
              setToBeDeleted(el.ID);
            }}
          >
            {trashIcon}
          </div>
          {/* <Link
            className="link"
            onClick={() => {
              setUrl(!url);
              setEditID(el.ID);
              setIdx(idx);
            }}
            style={{
              textDecoration: "none",
              color: "black",
            }}
            to={`${link}&id=${el.ID}`}
          >
            <div
              id="settings"
              style={{
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                padding: "7px",
                height: "28px",
                width: "28px",
              }}
              // onClick={() => handleClick(el?.ID)}
            >
              {settingsIcon}
            </div>
          </Link> */}
          <a 
            href={el.aero_page_id ? `https://tools.aeropage.io/api-connector/${el.aero_page_id}` : ""}
            target={ el.aero_page_id ? "_blank": "_self" }
            title={el.aero_page_id ? "Open project in Aeropage Tools." : "No project ID found. Please save the post type again and a link to open the project will be generated."}
          >
            <div
              id="settings"
              style={{
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                padding: "7px",
                height: "28px",
                width: "28px",
              }}
            >
              {aeroIconBlack}
            </div>
          </a>
          <div
            id="refresh"
            className={refreshState ? "refresh-start" : ""}
            style={{
              display: "flex",
              justifyContent: "center",
              alignItems: "center",
              padding: "7px",
              height: "28px",
              width: "28px",
              cursor: "pointer"
            }}
            onClick={() => {
              setRefreshState(true);
              handleRefresh(el?.ID).then((data) => {
                console.log("HANDLE REFRESH DATA: ", data);
                setSyncTime(data?.sync_time);
                setSyncStatus(data?.status);
                setRefreshState(false)
              });
            }}
          >
            {refreshIcon}
          </div>
        </div>
      </div>


      {/* <p style={{ fontSize: "12px" }}>
        Create rich html with images and buttons to use in emails.
        Export to Airtable and send using any automation tools
      </p> */}
    </div>
  );
};
export default Card;